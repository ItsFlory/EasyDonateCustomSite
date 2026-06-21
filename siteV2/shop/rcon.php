<?php

class MinecraftRcon
{
    private $socket;
    private $authorized = false;
    private $requestId = 0;

    const TYPE_AUTH = 3;
    const TYPE_AUTH_RESP = 2;
    const TYPE_COMMAND = 2;
    const TYPE_RESPONSE = 0;

    public function __construct(
        private string $host,
        private int $port,
        private string $password,
        private int $timeout = 3
    ) {}

    public function connect(): void
    {
        $errno = 0;
        $errstr = '';
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new RuntimeException("RCON: Could not connect ($errstr)");
        }

        stream_set_timeout($this->socket, $this->timeout);
        $this->authorize();
    }

    private function authorize(): void
    {
        $packetId = ++$this->requestId;
        $this->writePacket($packetId, self::TYPE_AUTH, $this->password);
        $response = $this->readPacket();

        if ($response['id'] === $packetId && $response['type'] === self::TYPE_AUTH_RESP) {
            $this->authorized = true;
        } else {
            $this->disconnect();
            throw new RuntimeException('RCON: Authentication failed');
        }
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->authorized = false;
    }

    public function isConnected(): bool
    {
        return $this->authorized && $this->socket !== null;
    }

    public function command(string $command): string
    {
        if (!$this->isConnected()) {
            throw new RuntimeException('RCON: Not connected');
        }

        $cmdId = ++$this->requestId;
        $this->writePacket($cmdId, self::TYPE_COMMAND, $command);

        $dummyId = ++$this->requestId;
        $this->writePacket($dummyId, self::TYPE_COMMAND, '');

        $response = '';
        $start = microtime(true);

        while (true) {
            if (microtime(true) - $start > $this->timeout) {
                throw new RuntimeException('RCON: Timeout while reading response');
            }

            $packet = $this->readPacket();

            if ($packet['id'] === $dummyId) {
                break;
            }

            if ($packet['id'] === $cmdId && $packet['type'] === self::TYPE_RESPONSE) {
                $response .= rtrim($packet['body'], "\x00");
            }
        }

        return $response;
    }

    private function writePacket(int $id, int $type, string $body): void
    {
        $packet = pack('VV', $id, $type) . $body . "\x00\x00";
        $packet = pack('V', strlen($packet)) . $packet;
        fwrite($this->socket, $packet, strlen($packet));
    }

    private function readPacket(): array
    {
        $sizeData = $this->readBytes(4);
        $size = unpack('V1size', $sizeData)['size'];
        $packetData = $this->readBytes($size);
        return unpack('V1id/V1type/a*body', $packetData);
    }

    private function readBytes(int $length): string
    {
        $data = '';
        $remaining = $length;
        $start = microtime(true);

        while ($remaining > 0) {
            if (microtime(true) - $start > $this->timeout) {
                throw new RuntimeException('RCON: Timeout reading from socket');
            }

            $chunk = fread($this->socket, $remaining);
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('RCON: Socket closed while reading');
            }

            $data .= $chunk;
            $remaining = $length - strlen($data);
        }

        return $data;
    }
}
