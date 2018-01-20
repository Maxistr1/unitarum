<?php

namespace UnitarumTest;

use PHPUnit\Framework\TestCase;
use Unitarum\DataBase;
use Unitarum\DataBaseInterface;
use Unitarum\Options;
use Unitarum\OptionsInterface;
use UnitarumExample\Entity\User;

class DataBaseTest extends TestCase
{
    const TEST_TABLE_USERS = 'test_users';
    const TEST_TABLE_ROLES = 'test_roles';

    use GetProtectedTrait;

    /**
     * @var DataBaseInterface
     */
    protected $dataBase;

    public function setUp()
    {
        $options = new Options([OptionsInterface::DSN_OPTION => 'sqlite:data/sqlite.db']);
        $this->dataBase = new DataBase($options);
    }

    public function testMergeArrays() {
        $firstEntity = new User();
        $firstEntity->setName('Test');
        $firstEntity->setEmail('test@test.no');

        $secondEntity = new User();
        $secondEntity->setName('SuperTest');

        $changedEntity = new User();
        $changedEntity->setName('SuperTest');
        $changedEntity->setEmail('test@test.no');

        $method = self::getProtectedMethod(DataBase::class, 'mergeArrays');
        $returnEntity = $method->invokeArgs($this->dataBase, [$firstEntity, $secondEntity]);
        $this->assertEquals($changedEntity, $returnEntity);
    }

    public function testMergeArrayWithoutSecond()
    {
        $firstEntity = new User();
        $firstEntity->setName('Test');
        $firstEntity->setEmail('test@test.no');

        $secondEntity = null;

        $method = self::getProtectedMethod(DataBase::class, 'mergeArrays');
        $returnEntity = $method->invokeArgs($this->dataBase, [$firstEntity, $secondEntity]);
        $this->assertEquals($firstEntity, $returnEntity);
    }

    public function testInsertDataFunctional()
    {
        /* Start transaction */
        $this->dataBase->startTransaction();

        $insertData = [
            'id' => 100,
            'email' => 'test@test.no',
            'name' => 'TestName'
        ];
        $methodInsert = self::getProtectedMethod(DataBase::class, 'insertData');
        $lastInsertId = $methodInsert->invokeArgs($this->dataBase, [$insertData, self::TEST_TABLE_USERS]);
        $this->assertNotFalse($lastInsertId);

        $methodSelect = self::getProtectedMethod(DataBase::class, 'selectById');
        $result = $methodSelect->invokeArgs($this->dataBase, [$lastInsertId, 'id', self::TEST_TABLE_USERS]);
        $this->assertEquals($insertData, $result);

        /* Rollback transaction */
        $this->dataBase->rollbackTransaction();
    }

    /**
     * @expectedException \Unitarum\Exception\DataBaseException
     */
    public function testInsertException()
    {
        $this->dataBase->startTransaction();
        $insertData = ['email' => 'test@test.no'];
        $methodInsert = self::getProtectedMethod(DataBase::class, 'insertData');
        $methodInsert->invokeArgs($this->dataBase, [$insertData, self::TEST_TABLE_USERS]);

        $methodInsert = self::getProtectedMethod(DataBase::class, 'insertData');
        $methodInsert->invokeArgs($this->dataBase, [$insertData, self::TEST_TABLE_USERS]);
    }

    public function testGetTableStructure()
    {
        $originalColumns = [
            AUTO_INCREMENT => 'id',
            'name',
            'email'
        ];
        $method = self::getProtectedMethod(DataBase::class, 'getTableStructure');
        $returnColumns = $method->invokeArgs($this->dataBase, [self::TEST_TABLE_USERS]);
        $this->assertEquals($originalColumns, $returnColumns);
    }

    public function tearDown()
    {
        $this->dataBase->rollbackTransaction();
    }
}