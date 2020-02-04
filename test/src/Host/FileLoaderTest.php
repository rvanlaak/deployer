<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Host;

use PHPUnit\Framework\TestCase;

class FileLoaderTest extends TestCase
{
    /**
     * @var Host[]
     */
    private $hosts;

    public function setUp(): void
    {
        $this->hosts = (new FileLoader())
            ->load(__DIR__ . '/../../fixture/inventory.yml')
            ->getHosts();
    }

    public function testLoad()
    {
        // .base does not exists
        self::assertNull($this->getHost('.base'), 'Hidden hosts exists in inventory');

        // foo extends .base
        $foo = $this->getHost('foo');
        self::assertInstanceOf(Host::class, $foo);
        self::assertEquals(['a', 'b', 'c'], $foo->get('roles'));

        // local is Localhost
        $local = $this->getHost('local');
        self::assertInstanceOf(Localhost::class, $local);
        self::assertEquals('/var/local', $local->get('deploy_to'));

        // bar configured properly
        $bar = $this->getHost('bar');
        self::assertEquals('bar', $bar->getHostname());
        self::assertEquals('user@bar.com', "$bar");
        self::assertEquals('user', $bar->getUser());
        self::assertEquals(22, $bar->getPort());
        self::assertEquals('configFile', $bar->getConfigFile());
        self::assertEquals('identityFile', $bar->getIdentityFile());
        self::assertTrue($bar->isForwardAgent());
        self::assertFalse($bar->isMultiplexing());
        self::assertEquals('param', $bar->get('param'));
        self::assertEquals(
            '-f -A -someFlag value -p 22 -F configFile -i identityFile -o Option=Value',
            $bar->getSshArguments()->getCliArguments()
        );

        $db1 = $this->getHost('db1.deployer.org');
        self::assertEquals('db1.deployer.org', $db1->getHostname());
        $db2 = $this->getHost('db2.deployer.org');
        self::assertEquals('db2.deployer.org', $db2->getHostname());
    }

    public function testBinaries()
    {
        $edge = $this->getHost('edge.deployer.org');
        self::assertEquals('php8', $edge->getBinary('php'));
    }

    /**
     * @param $name
     * @return Host|null
     */
    private function getHost($name)
    {
        foreach ($this->hosts as $host) {
            if ($host->getHostname() === $name) {
                return $host;
            }
        }
        return null;
    }
}
