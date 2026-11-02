<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/nodelock.inc.php';

// The database lock that keeps shared work to one node. SQL Server, MariaDB and PostgreSQL are tried by the boot
// tests (proxmox/test-nodelock.inc.sh); here, what needs no server.
final class NodeLockTest extends TestCase
{
    public function testKeysAreNamespacedAndFitMariaDbsLimit(): void
    {
        $this->assertSame('wifidb:mvtd_daily', NodeLock::Key('mvtd_daily'));
        $long = NodeLock::Key(str_repeat('x', 100));
        $this->assertLessThanOrEqual(64, strlen($long));
        $this->assertStringStartsWith('wifidb:', $long);
        $this->assertNotSame($long, NodeLock::Key(str_repeat('y', 100)), 'two long names stay two locks');
    }

    public function testSqliteIsOneProcessSoTheLockIsAlwaysFree(): void
    {
        $conn = new PDO('sqlite::memory:');
        $this->assertTrue(NodeLock::Take($conn, 'anything'));
        $this->assertTrue(NodeLock::Take($conn, 'anything'));
        NodeLock::Release($conn, 'anything');
        $this->assertTrue(NodeLock::Take($conn, 'anything'));
    }
}
