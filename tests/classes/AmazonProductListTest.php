<?php
use Kkgerry\AmazonMws\AmazonProductList;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-12 at 13:17:14.
 */
class AmazonProductListTest extends PHPUnit_Framework_TestCase {

    /**
     * @var AmazonProductList
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        resetLog();
        $this->object = new AmazonProductList('testStore', true, null);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }
    
    public function testSetIdType(){
        $this->assertFalse($this->object->setIdType(null)); //can't be nothing
        $this->assertFalse($this->object->setIdType(5)); //can't be an int
        $this->assertNull($this->object->setIdType('ASIN'));
        $o = $this->object->getOptions();
        $this->assertArrayHasKey('IdType',$o);
        $this->assertEquals('ASIN',$o['IdType']);
    }
    
    public function testSetProductIds(){
        $this->assertNull($this->object->setProductIds(array('123','456')));
        $o = $this->object->getOptions();
        $this->assertArrayHasKey('IdList.Id.1',$o);
        $this->assertEquals('123',$o['IdList.Id.1']);
        $this->assertArrayHasKey('IdList.Id.2',$o);
        $this->assertEquals('456',$o['IdList.Id.2']);
        
        $this->assertNull($this->object->setProductIds('789')); //causes reset
        $o2 = $this->object->getOptions();
        $this->assertEquals('789',$o2['IdList.Id.1']);
        $this->assertArrayNotHasKey('IdList.Id.2',$o2);
        
        $this->assertFalse($this->object->setProductIds(null));
        $this->assertFalse($this->object->setProductIds(707));
    }
    
    public function testFetchProductList(){
        resetLog();
        $this->object->setMock(true,'fetchProductList.xml');
        $this->assertFalse($this->object->fetchProductList()); //no IDs yet
        $this->object->setProductIds('789');
        
        $this->assertFalse($this->object->fetchProductList()); //no ID type yet
        $this->object->setIdType('ASIN');
        
        $this->assertNull($this->object->fetchProductList());
        
        $o = $this->object->getOptions();
        $this->assertEquals('GetMatchingProductForId',$o['Action']);
        
        $check = parseLog();
        $this->assertEquals('Single Mock File set: fetchProductList.xml',$check[1]);
        $this->assertEquals('Product IDs must be set in order to fetch them!',$check[2]);
        $this->assertEquals('ID Type must be set in order to use the given IDs!',$check[3]);
        $this->assertEquals('Fetched Mock File: mock/fetchProductList.xml',$check[4]);
        
        return $this->object;
    }
    
    /**
     * @depends testFetchProductList
     */
    public function testGetProduct($o){
        $product = $o->getProduct(0);
        $this->assertInternalType('object',$product);
        
        $list = $o->getProduct(null);
        $this->assertInternalType('array',$list);
        $this->assertArrayHasKey(0,$list);
        $this->assertEquals($product,$list[0]);
        
        $default = $o->getProduct();
        $this->assertEquals($list,$default);
        
        $check = $product->getData();
        $this->assertArrayHasKey('Identifiers',$check);
        $this->assertArrayHasKey('SalesRankings',$check);
        
        $x = array();
        $x[0] = $o->getProduct(0);
        $x[1] = $o->getProduct(1);
        $x[2] = $o->getProduct(2);
        
        $this->assertEquals($x, $list);
        
        $this->assertFalse($this->object->getProduct()); //not fetched yet for this object
    }
    
}

require_once(__DIR__.'/../helperFunctions.php');