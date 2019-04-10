<?php
use Kkgerry\AmazonMws\AmazonFulfillmentOrderList;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-12 at 13:17:14.
 */
class AmazonFulfillmentOrderListTest extends PHPUnit_Framework_TestCase {

    /**
     * @var AmazonFulfillmentOrderList
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        resetLog();
        $this->object = new AmazonFulfillmentOrderList('testStore', true, null);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }
    
    public function testSetStartTime(){
        $this->assertFalse($this->object->setStartTime(null)); //can't be nothing
        $this->assertFalse($this->object->setStartTime(5)); //can't be an int
        $this->assertNull($this->object->setStartTime('-1 min'));
        $o = $this->object->getOptions();
        $this->assertArrayHasKey('QueryStartDateTime',$o);
        $this->assertNotEquals('1969-12-31T18:58:00-0500',$o['QueryStartDateTime']);
    }
    
    public function testSetMethodFilter(){
        $this->assertFalse($this->object->setMethodFilter(null)); //can't be nothing
        $this->assertFalse($this->object->setMethodFilter(5)); //can't be an int
        $this->assertFalse($this->object->setMethodFilter('wrong')); //not a valid value
        $this->assertNull($this->object->setMethodFilter('Consumer'));
        $this->assertNull($this->object->setMethodFilter('Removal'));
        $o = $this->object->getOptions();
        $this->assertArrayHasKey('FulfillmentMethod',$o);
        $this->assertEquals('Removal',$o['FulfillmentMethod']);
    }
    
    public function testSetUseToken(){
        $this->assertNull($this->object->setUseToken());
        $this->assertNull($this->object->setUseToken(true));
        $this->assertNull($this->object->setUseToken(false));
        $this->assertFalse($this->object->setUseToken('wrong'));
    }
    


    public function testFetchOrderList(){
        resetLog();
        $this->object->setMock(true,array('fetchFulfillmentOrderList.xml')); //no token
        $this->assertNull($this->object->fetchOrderList());
        
        $o = $this->object->getOptions();
        $this->assertEquals('ListAllFulfillmentOrders',$o['Action']);
        
        $r = $this->object->getOrder(null);
        $this->assertArrayHasKey(0,$r);
        $this->assertInternalType('array',$r[0]);
        $this->assertInternalType('array',$r[1]);
        
        
        $check = parseLog();
        $this->assertEquals('Mock files array set.',$check[1]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrderList.xml',$check[2]);
        
        
        $this->assertFalse($this->object->hasToken());
        
        return $this->object;
    }
    
    /**
     * @depends testFetchOrderList
     */
    public function testGetOrder($o){
        $order1 = $o->getOrder(0);
        $order2 = $o->getOrder(1);
        $this->assertInternalType('array',$order1);
        $this->assertInternalType('array',$order2);
        
        $list = $o->getOrder(null);
        $this->assertInternalType('array',$list);
        $this->assertArrayHasKey(0,$list);
        $this->assertArrayHasKey(1,$list);
        $this->assertEquals($order1,$list[0]);
        $this->assertEquals($order2,$list[1]);
        
        $default = $o->getOrder();
        $this->assertEquals($list,$default);
        
        $this->assertFalse($this->object->getOrder()); //not fetched yet for this object
    }
    
    public function testGetFullList(){
        resetLog();
        $this->object->setMock(true,array('fetchFulfillmentOrderList.xml','fetchFulfillmentOrder.xml','fetchFulfillmentOrder.xml')); //need files for orders
        $this->assertNull($this->object->fetchOrderList());
        
        $o = $this->object->getOptions();
        $this->assertEquals('ListAllFulfillmentOrders',$o['Action']);
        
        $r = $this->object->getFullList();
        $this->assertArrayHasKey(0,$r);
        $this->assertInternalType('object',$r[0]);
        $this->assertInternalType('object',$r[1]);
        $this->assertInternalType('array',$r[0]->getOrder());
        $this->assertInternalType('array',$r[1]->getOrder());
        
        $check = parseLog();
        $this->assertEquals('Mock files array set.',$check[1]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrderList.xml',$check[2]);
        $this->assertEquals('Mock Mode set to ON',$check[3]);
        $this->assertEquals('Mock files array set.',$check[4]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrder.xml',$check[5]);
        $this->assertEquals('Mock Mode set to ON',$check[6]);
        $this->assertEquals('Mock files array set.',$check[7]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrder.xml',$check[8]);
    }
    
    public function testFetchOrderListToken1(){
        resetLog();
        $this->object->setMock(true,array('fetchFulfillmentOrderListToken.xml'));
        
        //without using token
        $this->assertNull($this->object->fetchOrderList());
        $check = parseLog();
        $this->assertEquals('Mock files array set.',$check[1]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrderListToken.xml',$check[2]);
        $this->assertTrue($this->object->hasToken());
        $o = $this->object->getOptions();
        $this->assertEquals('ListAllFulfillmentOrders',$o['Action']);
        $r = $this->object->getOrder(null);
        $this->assertArrayHasKey(0,$r);
        $this->assertEquals('extern_id_1154539615776',$r[0]['SellerFulfillmentOrderId']);
        $this->assertArrayNotHasKey(1,$r);
    }
    
    public function testFetchOrderListToken2(){
        resetLog();
        $this->object->setMock(true,array('fetchFulfillmentOrderListToken.xml','fetchFulfillmentOrderListToken2.xml'));
        
        //with using token
        $this->object->setUseToken();
        $this->assertNull($this->object->fetchOrderList());
        $check = parseLog();
        $this->assertEquals('Mock files array set.',$check[1]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrderListToken.xml',$check[2]);
        $this->assertEquals('Recursively fetching more Orders',$check[3]);
        $this->assertEquals('Fetched Mock File: mock/fetchFulfillmentOrderListToken2.xml',$check[4]);
        $this->assertFalse($this->object->hasToken());
        $o = $this->object->getOptions();
        $this->assertEquals('ListAllFulfillmentOrdersByNextToken',$o['Action']);
        $r = $this->object->getOrder(null);
        $this->assertArrayHasKey(0,$r);
        $this->assertArrayHasKey(1,$r);
        $this->assertEquals('extern_id_1154539615776',$r[0]['SellerFulfillmentOrderId']);
        $this->assertEquals('external-order-ebaytime1154557376014',$r[1]['SellerFulfillmentOrderId']);
        $this->assertNotEquals($r[0],$r[1]);
    }
    
}

require_once(__DIR__.'/../helperFunctions.php');