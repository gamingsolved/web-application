<?php

namespace Tests\AppBundle\Resources;

class TagSgxTwigTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        `rm -rf /var/tmp/ubiqmachine-twig-tests-cache`;
        `mkdir -p /var/tmp/ubiqmachine-twig-tests-cache`;

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/../../../../../../src/AppBundle/Resources/views/remoteDesktop/sgxFile/');
        $twig = new \Twig_Environment($loader, array(
            'cache' => '/var/tmp/ubiqmachine-twig-tests-cache',
        ));

        $template = $twig->load('tag.sgx.twig');

        $expected = <<<'EOD'
#SCGX1.1
ip: 121.122.123.124
key: abcd
video-port: 40000
input-port: 40001
audio-port: 40002
config-port: 40003
audio-in-port: 40006
usb-port: 40010
bitrate: 15
audio: on
loglevel: SGXLOG_ERROR
client-type: CloudGaming
mouse-local: off
mouse-remote: on
force-software-decode: off
performance-setting: latency
width: 1280
height: 800
user: Administrator
password: Z-&wuZk9n6K
network-game-mode: on
use-encryption: off

EOD;


        $this->assertSame(
            $expected,
            $template->render(
                [
                    'ip'  => '121.122.123.124',
                    'key' => 'abcd',
                    'width' => '1280',
                    'height' => '800',
                    'password' => 'Z-&wuZk9n6K'
                ]
            )
        );
    }
}
