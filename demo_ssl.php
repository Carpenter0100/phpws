#!/php -q
<?php
// Run from command prompt > php demo.php
use Devristo\Phpws\Framing\WebSocketFrame;
use Devristo\Phpws\Framing\WebSocketOpcode;
use Devristo\Phpws\Messaging\IWebSocketMessage;
use Devristo\Phpws\Protocol\IWebSocketConnection;
use Devristo\Phpws\Server\IWebSocketServerObserver;
use Devristo\Phpws\Server\UriHandler\WebSocketUriHandler;
use Devristo\Phpws\Server\WebSocketServer;

/**
 * This demo resource handler will respond to all messages sent to /echo/ on the socketserver below
 *
 * All this handler does is echoing the responds to the user
 * @author Chris
 *
 */
class DemoSslEchoHandler extends WebSocketUriHandler {

    public function onMessage(IWebSocketConnection $user, IWebSocketMessage $msg) {
        $this->say("[ECHO] {$msg->getData()}");
        // Echo
        $user->sendMessage($msg);
    }

    public function onAdminMessage(IWebSocketConnection $user, IWebSocketMessage $obj) {
        $this->say("[DEMO] Admin TEST received!");

        $frame = WebSocketFrame::create(WebSocketOpcode::PongFrame);
        $user->sendFrame($frame);
    }

}

/**
 * Demo socket server. Implements the basic eventlisteners and attaches a resource handler for /echo/ urls.
 *
 *
 * @author Chris
 *
 */
class DemoSslSocketServer implements IWebSocketServerObserver {

    protected $debug = true;
    protected $server;

    public function __construct() {
        $this->server = new WebSocketServer("ssl://0.0.0.0:12345");
        $this->server->addObserver($this);

        $this->server->addUriHandler("echo", new DemoSslEchoHandler());

        $this->setupSSL();
    }

    private function getPEMFilename() {
        return './democert.pem';
    }

    public function setupSSL() {
        $context = stream_context_create();

        // local_cert must be in PEM format
        stream_context_set_option($context, 'ssl', 'local_cert', $this->getPEMFilename());

        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);

        $this->server->setStreamContext($context);
    }

    public function onConnect(IWebSocketConnection $user) {
        $this->say("[DEMO] {$user->getId()} connected");
    }

    public function onMessage(IWebSocketConnection $user, IWebSocketMessage $msg) {
        $this->say("[DEMO] {$user->getId()} says '{$msg->getData()}'");
    }

    public function onDisconnect(IWebSocketConnection $user) {
        $this->say("[DEMO] {$user->getId()} disconnected");
    }

    public function onAdminMessage(IWebSocketConnection $user, IWebSocketMessage $msg) {
        $this->say("[DEMO] Admin Message received!");

        $frame = WebSocketFrame::create(WebSocketOpcode::PongFrame);
        $user->sendFrame($frame);
    }

    public function say($msg) {
        echo "$msg \r\n";
    }

    public function run() {
        $this->server->run();
    }

}

// Start server
$server = new DemoSslSocketServer();
$server->run();