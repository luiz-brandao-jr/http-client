<?php

class ProviderTest extends PHPUnit_Framework_TestCase
{
    public function testBeginsEmpty()
    {
        $dp = new ProviderCoverageTest;
        $this->assertEquals([], $dp->definitions);
        $this->assertEquals([], $dp->shared);
        return $dp;
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getInjectedInstance
     * @covers Artax\Ioc\Provider::getDepsSansDefinition
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     */
    public function testMakeInjectsSimpleConcreteDeps($dp)
    {
        $injected = $dp->make('TestNeedsDep');
        $this->assertEquals($injected, new TestNeedsDep(new TestDependency));
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getInjectedInstance
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     */
    public function testMakeReturnsSharedInstanceIfSpecified($dp)
    {
        $dp->removeAll();
        $dp->define('TestNeedsDep', ['TestDependency']);
        $dp->define('TestDependency', ['_shared']);
        $injected = $dp->make('TestNeedsDep');
        $injected->testDep->testProp = 'something else';
        
        $injected2 = $dp->make('TestNeedsDep');
        $this->assertEquals('something else', $injected2->testDep->testProp);
        
        $shared = $dp->make('TestDependency');
        $this->assertEquals($injected->testDep, $shared);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getDepsSansDefinition
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     * @expectedException InvalidArgumentException
     */
    public function testMakeThrowsExceptionOnConstructorMissingTypehintsSansDefinitions($dp)
    {
        $dp->removeAll();
        $dp->make('TestClassWithNoCtorTypehints');
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getInjectedInstance
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     * @expectedException InvalidArgumentException
     */
    public function testMakeThrowsExceptionOnMissingDefinitionParams($dp)
    {
        $dp->removeAll();
        $dp->make('TestMultiDepsNeeded', ['TestDependency']);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getInjectedInstance
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     * @expectedException InvalidArgumentException
     */
    public function testMakeThrowsExceptionOnDefinitionParamOfIncorrectType($dp)
    {
        $dp->removeAll();
        $dp->make('TestMultiDepsNeeded', ['TestDependency', new StdClass]);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getInjectedInstance
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     */
    public function testMakeUsesInstanceDefinitionParamIfSpecified($dp)
    {
        $dp->removeAll();
        $dp->make('TestMultiDepsNeeded', ['TestDependency', new TestDependency2]);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::make
     * @covers Artax\Ioc\Provider::getInjectedInstance
     * @covers Artax\Ioc\Provider::getDepsWithDefinition
     */
    public function testMakeUsesCustomDefinitionIfSpecified($dp)
    {
        $dp->removeAll();
        $dp->define('TestNeedsDep', ['TestDependency']);
        $injected = $dp->make('TestNeedsDep', ['TestDependency2']);
        $this->assertEquals('testVal2', $injected->testDep->testProp);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::define
     */
    public function testDefineAssignsPassedDefinition($dp)
    {
        $dp->removeAll();
        $dp->define('TestNeedsDep', ['TestDependency']);
        $dp->define('TestDependency', ['_shared']);
        $this->assertEquals($dp->definitions['TestNeedsDep'], ['TestDependency']);
        $this->assertEquals($dp->definitions['TestDependency'], ['_shared']);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::define
     */
    public function testDefineRemovesSharedInstanceIfNewDefinitionIsNotShared($dp)
    {
        $dp->removeAll();
        $dp->define('TestDependency', ['_shared']);        
        $obj = $dp->make('TestDependency');
        $dp->define('TestDependency', ['_shared']);
        $this->assertEmpty($dp->shared);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::defineAll
     * @expectedException InvalidArgumentException
     */
    public function testDefineAllThrowsExceptionOnInvalidIterable($dp)
    {
        $dp->defineAll(1);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::defineAll
     */
    public function testDefineAllAssignsPassedDefinitionsAndReturnsAddedCount($dp)
    {
        $dp->removeAll();
        $depList = [];
        $depList['TestNeedsDep'] = ['TestDependency'];
        $depList['TestDependency'] = ['_shared'];
        
        $this->assertEquals(2, $dp->defineAll($depList));
        $this->assertEquals(['TestDependency'],
            $dp->definitions['TestNeedsDep']
        );
        $this->assertEquals(['_shared'], $dp->definitions['TestDependency']);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::remove
     */
    public function testRemoveClearsDefinitionAndSharedInstanceAndReturnsProvider($dp)
    {
        $dp->removeAll();
        $dp->define('TestDependency', ['_shared']);
        $obj = $dp->make('TestDependency');
        $return = $dp->remove('TestDependency');
        $this->assertEmpty($dp->shared);
        $this->assertFalse(isset($dp->definitions['TestDependency']));
        $this->assertEquals($return, $dp);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::removeAll
     */
    public function testRemoveAllClearsDefinitionAndSharedInstancesAndReturnsProvider($dp)
    {
        $dp->removeAll();
        $dp->define('TestDependency', ['_shared']);
        $obj = $dp->make('TestDependency');
        $this->assertEquals($dp->definitions['TestDependency'], ['_shared']);
        $this->assertEquals($dp->shared['TestDependency'], $obj);
        
        $return = $dp->removeAll();
        $this->assertEmpty($dp->shared);
        $this->assertEmpty($dp->definitions);
        $this->assertEquals($return, $dp);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::refresh
     */
    public function testRefreshClearsSharedInstancesAndReturnsProvider($dp)
    {
        $dp->removeAll();
        $dp->define('TestDependency', ['_shared']);
        $obj = $dp->make('TestDependency');
        $this->assertEquals($dp->shared['TestDependency'], $obj);
        
        $return = $dp->refresh('TestDependency');
        $this->assertEmpty($dp->shared);
        $this->assertEquals($return, $dp);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::isShared
     */
    public function testIsSharedReturnsSharedStatus($dp)
    {
        $dp->removeAll();
        
        $dp->define('TestDependency', ['_shared']);
        $this->assertFalse($dp->isShared('TestDependency'));
        $obj = $dp->make('TestDependency');
        $this->assertEquals($dp->shared['TestDependency'], $obj);
        $this->assertTrue($dp->isShared('TestDependency'));
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::isDefined
     */
    public function testIsDefinedReturnsDefinedStatus($dp)
    {
        $dp->removeAll();
        $this->assertFalse($dp->isDefined('TestDependency'));
        $dp->define('TestDependency', ['_shared']);
        $this->assertTrue($dp->isDefined('TestDependency'));
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::offsetSet
     */
    public function testOffsetSetCallsDefine($dp)
    {
        $stub = $this->getMock('Artax\Ioc\Provider', ['define']);
        $stub->expects($this->once())
             ->method('define');
        $stub['TestNeedsDep'] = ['_shared'];
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::offsetUnset
     */
    public function testOffsetUnsetCallsRemove($dp)
    {
        $stub = $this->getMock('Artax\Ioc\Provider', ['remove']);
        $stub->expects($this->once())
             ->method('remove');
        $stub['TestNeedsDep'] = ['_shared'];
        unset($stub['TestNeedsDep']);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::offsetExists
     */
    public function testOffsetExistsReturnsExpected($dp)
    {
        $dp->removeAll();
        $dp->define('TestNeedsDep', ['TestDependency']);
        $obj = $dp->make('TestNeedsDep');
        $this->assertTrue(isset($dp['TestNeedsDep']));
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::offsetGet
     */
    public function testOffsetGetReturnsExpected($dp)
    {
        $dp->removeAll();
        $dp->define('TestNeedsDep', ['TestDependency']);
        $obj = $dp->make('TestNeedsDep');
        $this->assertEquals($dp->definitions['TestNeedsDep'], $dp['TestNeedsDep']);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::share
     */
    public function testShareStoresSharedDependencyAndReturnsChainableInstance($dp)
    {
        $dp->removeAll();
        $testShare = new StdClass;
        $return = $dp->share('StdClass', $testShare);
        $this->assertEquals($testShare, $dp->shared['StdClass']);
        $this->assertEquals($dp, $return);
    }
    
    /**
     * @depends testBeginsEmpty
     * @covers Artax\Ioc\Provider::share
     * @expectedException InvalidArgumentException
     */
    public function testShareThrowsExceptionOnInvalidArgument($dp)
    {
        $testShare = new StdClass;
        $dp->share('Artax\Events\Mediator', $testShare);
    }
}

class ProviderCoverageTest extends Artax\Ioc\Provider
{
    use MagicTestGetTrait;
}

class TestDependency
{
    public $testProp = 'testVal';
}

class TestDependency2 extends TestDependency
{
    public $testProp = 'testVal2';
}

class SpecdTestDependency extends TestDependency
{
    public $testProp = 'testVal';
}

class TestNeedsDep
{
    public function __construct(TestDependency $testDep)
    {
        $this->testDep = $testDep;
    }
}

class TestClassWithNoCtorTypehints
{
    public function __construct($val)
    {
        $this->test = $val;
    }
}

class TestMultiDepsNeeded
{
    public function __construct(TestDependency $val1, TestDependency2 $val2)
    {
        $this->testDep = $val1;
        $this->testDep = $val2;
    }
}