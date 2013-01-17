<?php
namespace Pomander;

class SSH2 extends \Net_SSH2
{
	public function exec($command, $block = false)
	{
		$this->curTimeout = $this->timeout;

		if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
				return false;
		}

		// RFC4254 defines the (client) window size as "bytes the other party can send before it must wait for the window to
		// be adjusted".  0x7FFFFFFF is, at 4GB, the max size.  technically, it should probably be decremented, but, 
		// honestly, if you're transfering more than 4GB, you probably shouldn't be using phpseclib, anyway.
		// see http://tools.ietf.org/html/rfc4254#section-5.2 for more info
		$this->window_size_client_to_server[NET_SSH2_CHANNEL_EXEC] = 0x7FFFFFFF;
		// 0x8000 is the maximum max packet size, per http://tools.ietf.org/html/rfc4253#section-6.1, although since PuTTy
		// uses 0x4000, that's what will be used here, as well.
		$packet_size = 0x4000;

		$packet = pack('CNa*N3',
			NET_SSH2_MSG_CHANNEL_OPEN, strlen('session'), 'session', NET_SSH2_CHANNEL_EXEC, $this->window_size_client_to_server[NET_SSH2_CHANNEL_EXEC], $packet_size);

		if (!$this->_send_binary_packet($packet)) {
			return false;
		}

		$this->channel_status[NET_SSH2_CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_OPEN;

		$response = $this->_get_channel_packet(NET_SSH2_CHANNEL_EXEC);
		if ($response === false) {
			return false;
		}

		//send request-pty
		$terminal_modes = pack('C', NET_SSH2_TTY_OP_END);
		$packet = pack('CNNa*CNa*N5a*',
			NET_SSH2_MSG_CHANNEL_REQUEST, $this->server_channels[NET_SSH2_CHANNEL_EXEC], strlen('pty-req'), 'pty-req', 1, strlen('vt100'), 'vt100',
			80, 24, 0, 0, strlen($terminal_modes), $terminal_modes);

		if (!$this->_send_binary_packet($packet)) {
			return false;
		}
		$response = $this->_get_binary_packet();
		if ($response === false) {
			user_error('Connection closed by server');
			return false;
		}

		list(, $type) = unpack('C', $this->_string_shift($response, 1));

		switch ($type) {
			case NET_SSH2_MSG_CHANNEL_SUCCESS:
				break;
			case NET_SSH2_MSG_CHANNEL_FAILURE:
			default:
				user_error('Unable to request pseudo-terminal');
				return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
		}

		$packet = pack('CNNa*CNa*',
			NET_SSH2_MSG_CHANNEL_REQUEST, $this->server_channels[NET_SSH2_CHANNEL_EXEC], strlen('exec'), 'exec', 1, strlen($command), $command);
		if (!$this->_send_binary_packet($packet)) {
			return false;
		}

		$this->channel_status[NET_SSH2_CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_REQUEST;

		$response = $this->_get_channel_packet(NET_SSH2_CHANNEL_EXEC);
		if ($response === false) {
			return false;
		}

		$this->channel_status[NET_SSH2_CHANNEL_EXEC] = NET_SSH2_MSG_CHANNEL_DATA;

		if (!$block) {
			return true;
		}

		$output = '';
		while (true) {
			$temp = $this->_get_channel_packet(NET_SSH2_CHANNEL_EXEC);
			switch (true) {
				case $temp === true:
					return $output;
				case $temp === false:
					return false;
				default:
					$output.= $temp;
			}
		}
	}


	function _get_channel_packet($client_channel, $skip_extended = false)
	{
		if (!empty($this->channel_buffers[$client_channel])) {
			return array_shift($this->channel_buffers[$client_channel]);
		}

		while (true) {
			if ($this->curTimeout) {
				$read = array($this->fsock);
				$write = $except = NULL;

				$start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838
				$sec = floor($this->curTimeout);
				$usec = 1000000 * ($this->curTimeout - $sec);
				// on windows this returns a "Warning: Invalid CRT parameters detected" error
				if (!@stream_select($read, $write, $except, $sec, $usec) && !count($read)) {
					$this->_close_channel($client_channel);
					return true;
				}
				$elapsed = strtok(microtime(), ' ') + strtok('') - $start;
				$this->curTimeout-= $elapsed;
			}

			$response = $this->_get_binary_packet();
			if ($response === false) {
				user_error('Connection closed by server');
				return false;
			}

			if (!strlen($response)) {
				return '';
			}

			extract(unpack('Ctype/Nchannel', $this->_string_shift($response, 5)));

			switch ($this->channel_status[$channel]) {
				case NET_SSH2_MSG_CHANNEL_OPEN:
					switch ($type) {
						case NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION:
							extract(unpack('Nserver_channel', $this->_string_shift($response, 4)));
							$this->server_channels[$channel] = $server_channel;
							$this->_string_shift($response, 4); // skip over (server) window size
							$temp = unpack('Npacket_size_client_to_server', $this->_string_shift($response, 4));
							$this->packet_size_client_to_server[$channel] = $temp['packet_size_client_to_server'];
							return $client_channel == $channel ? true : $this->_get_channel_packet($client_channel, $skip_extended);
						//case NET_SSH2_MSG_CHANNEL_OPEN_FAILURE:
						default:
							user_error('Unable to open channel');
							return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
					}
					break;
				case NET_SSH2_MSG_CHANNEL_REQUEST:
					switch ($type) {
						case NET_SSH2_MSG_CHANNEL_SUCCESS:
							return true;
						//case NET_SSH2_MSG_CHANNEL_FAILURE:
						default:
							user_error('Unable to request pseudo-terminal');
							return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
					}
				case NET_SSH2_MSG_CHANNEL_CLOSE:
					return $type == NET_SSH2_MSG_CHANNEL_CLOSE ? true : $this->_get_channel_packet($client_channel, $skip_extended);
			}

			switch ($type) {
				case NET_SSH2_MSG_CHANNEL_DATA:
					/*
					if ($client_channel == NET_SSH2_CHANNEL_EXEC) {
							// SCP requires null packets, such as this, be sent.  further, in the case of the ssh.com SSH server
							// this actually seems to make things twice as fast.  more to the point, the message right after 
							// SSH_MSG_CHANNEL_DATA (usually SSH_MSG_IGNORE) won't block for as long as it would have otherwise.
							// in OpenSSH it slows things down but only by a couple thousandths of a second.
							$this->_send_channel_packet($client_channel, chr(0));
					}
					*/
					extract(unpack('Nlength', $this->_string_shift($response, 4)));
					$data = $this->_string_shift($response, $length);
					if ($client_channel == $channel) {
						return $data;
					}
					if (!isset($this->channel_buffers[$client_channel])) {
						$this->channel_buffers[$client_channel] = array();
					}
					$this->channel_buffers[$client_channel][] = $data;
					break;
				case NET_SSH2_MSG_CHANNEL_EXTENDED_DATA:
					if ($skip_extended || $this->quiet_mode) {
						break;
					}
					/*
					if ($client_channel == NET_SSH2_CHANNEL_EXEC) {
							$this->_send_channel_packet($client_channel, chr(0));
					}
					*/
					// currently, there's only one possible value for $data_type_code: NET_SSH2_EXTENDED_DATA_STDERR
					extract(unpack('Ndata_type_code/Nlength', $this->_string_shift($response, 8)));
					$data = $this->_string_shift($response, $length);
					if ($client_channel == $channel) {
						return $data;
					}
					if (!isset($this->channel_buffers[$client_channel])) {
						$this->channel_buffers[$client_channel] = array();
					}
					$this->channel_buffers[$client_channel][] = $data;
					break;
				case NET_SSH2_MSG_CHANNEL_REQUEST:
					extract(unpack('Nlength', $this->_string_shift($response, 4)));
					$value = $this->_string_shift($response, $length);
					switch ($value) {
						case 'exit-signal':
							$this->_string_shift($response, 1);
							extract(unpack('Nlength', $this->_string_shift($response, 4)));
							$this->errors[] = 'SSH_MSG_CHANNEL_REQUEST (exit-signal): ' . $this->_string_shift($response, $length);
							$this->_string_shift($response, 1);
							extract(unpack('Nlength', $this->_string_shift($response, 4)));
							if ($length) {
									$this->errors[count($this->errors)].= "\r\n" . $this->_string_shift($response, $length);
							}
						case 'exit-status':
							extract(unpack('Cfalse/Nexit_status', $this->_string_shift($response, 5)));
							$this->exit_status = $exit_status;
							// "The channel needs to be closed with SSH_MSG_CHANNEL_CLOSE after this message."
							// -- http://tools.ietf.org/html/rfc4254#section-6.10
							$this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_EOF, $this->server_channels[$client_channel]));
							$this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$channel]));

							$this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_EOF;
						default:
							// "Some systems may not implement signals, in which case they SHOULD ignore this message."
							//  -- http://tools.ietf.org/html/rfc4254#section-6.9
							break;
					}
					break;
				case NET_SSH2_MSG_CHANNEL_CLOSE:
					$this->curTimeout = 0;

					if ($this->bitmap & NET_SSH2_MASK_SHELL) {
						$this->bitmap&= ~NET_SSH2_MASK_SHELL;
					}
					if ($this->channel_status[$channel] != NET_SSH2_MSG_CHANNEL_EOF) {
						$this->_send_binary_packet(pack('CN', NET_SSH2_MSG_CHANNEL_CLOSE, $this->server_channels[$channel]));
					}

					$this->channel_status[$channel] = NET_SSH2_MSG_CHANNEL_CLOSE;
					return true;
				case NET_SSH2_MSG_CHANNEL_EOF:
					break;
				default:
					user_error('Error reading channel data');
					return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
			}
		}
	}
}
