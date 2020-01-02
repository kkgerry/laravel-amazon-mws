<?php namespace Kkgerry\AmazonMws;

use Kkgerry\AmazonMws\AmazonInboundCore;

/**
 * Copyright 2013 CPI Group, LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 *
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Submits a shipment to Amazon or updates it.
 *
 * This Amazon Inbound Core object submits a request to create an inbound
 * shipment with Amazon. It can also update existing shipments. In order to
 * create or update a shipment, information from a Shipment Plan is required.
 * Use the AmazonShipmentPlanner object to retrieve this information.
 */
class AmazonShipment extends AmazonInboundCore
{
    private $shipmentId;

    /**
     * AmazonShipment ubmits a shipment to Amazon or updates it.
     *
     * The parameters are passed to the parent constructor, which are
     * in turn passed to the AmazonCore constructor. See it for more information
     * on these parameters and common methods.
     * @param string $s <p>Name for the store you want to use.</p>
     * @param boolean $mock [optional] <p>This is a flag for enabling Mock Mode.
     * This defaults to <b>FALSE</b>.</p>
     * @param array|string $m [optional] <p>The files (or file) to use in Mock Mode.</p>
     * @param string $config [optional] <p>An alternate config file to set. Used for testing.</p>
     */
    public function __construct($s, $mock = false, $m = null)
    {
        parent::__construct($s, $mock, $m);

        $this->options['InboundShipmentHeader.ShipmentStatus'] = 'WORKING';
    }

    /**
     * Automatically fills in the necessary fields using a header array.
     *
     * This information is required to submit a shipment.
     * @param array $x <p>plan array from <i>AmazonShipmentPlanner</i></p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function setHeader($x)
    {
        if (is_array($x) && $x['ShipmentName']&& $x['DestinationFulfillmentCenterId'] && $x['LabelPrepPreference']) {
            $this->options['InboundShipmentHeader.ShipmentName'] = $x['ShipmentName'];
            $this->options['InboundShipmentHeader.DestinationFulfillmentCenterId'] = $x['DestinationFulfillmentCenterId'];
            $this->options['InboundShipmentHeader.LabelPrepPreference'] = $x['LabelPrepPreference'];

        } else {
            $this->log("setHeader requires an array", 'Warning');
            return false;
        }
    }

    /**
     * Automatically fills in the necessary fields using a planner array.
     *
     * This information is required to submit a shipment.
     * @param array $x <p>plan array from <i>AmazonShipmentPlanner</i></p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function usePlan($x)
    {
        if (is_array($x)) {
            $this->options['ShipmentId'] = $x['ShipmentId'];

            if(isset($x['ShipmentName'])){
                $this->options['InboundShipmentHeader.ShipmentName'] = $x['ShipmentName'];
            }

            //inheriting address
            $this->setAddress($x['ShipToAddress']);

            $this->options['InboundShipmentHeader.ShipmentId'] = $x['ShipmentId'];
            $this->options['InboundShipmentHeader.DestinationFulfillmentCenterId'] = $x['DestinationFulfillmentCenterId'];
            $this->options['InboundShipmentHeader.LabelPrepType'] = $x['LabelPrepType'];

            $this->setItems($x['Items']);

        } else {
            $this->log("usePlan requires an array", 'Warning');
            return false;
        }
    }

    /**
     * Sets the address. (Required)
     *
     * This method sets the destination address to be sent in the next request.
     * This parameter is required for creating a fulfillment order with Amazon.
     * The array provided should have the following fields:
     * <ul>
     * <li><b>Name</b> - max: 50 char</li>
     * <li><b>AddressLine1</b> - max: 180 char</li>
     * <li><b>AddressLine2</b> (optional) - max: 60 char</li>
     * <li><b>City</b> - max: 30 char</li>
     * <li><b>DistrictOrCounty</b> (optional) - max: 25 char</li>
     * <li><b>StateOrProvinceCode</b> (recommended) - 2 digits</li>
     * <li><b>CountryCode</b> - 2 digits</li>
     * <li><b>PostalCode</b> - max: 30 char</li>
     * </ul>
     * @param array $a <p>See above.</p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function setAddress($a)
    {
        if (!$a || is_null($a) || is_string($a)) {
            $this->log("Tried to set address to invalid values", 'Warning');
            return false;
        }
        if (!array_key_exists('AddressLine1', $a)) {
            $this->resetAddress();
            $this->log("Tried to set address with invalid array", 'Warning');
            return false;
        }
        $this->resetAddress();
        $this->options['InboundShipmentHeader.ShipFromAddress.Name'] = $a['Name'];
        $this->options['InboundShipmentHeader.ShipFromAddress.AddressLine1'] = $a['AddressLine1'];
        if (array_key_exists('AddressLine2', $a)) {
            $this->options['InboundShipmentHeader.ShipFromAddress.AddressLine2'] = $a['AddressLine2'];
        } else {
            $this->options['InboundShipmentHeader.ShipFromAddress.AddressLine2'] = null;
        }
        $this->options['InboundShipmentHeader.ShipFromAddress.City'] = $a['City'];
        if (array_key_exists('DistrictOrCounty', $a)) {
            $this->options['InboundShipmentHeader.ShipFromAddress.DistrictOrCounty'] = $a['DistrictOrCounty'];
        } else {
            $this->options['InboundShipmentHeader.ShipFromAddress.DistrictOrCounty'] = null;
        }
        $this->options['InboundShipmentHeader.ShipFromAddress.StateOrProvinceCode'] = $a['StateOrProvinceCode'];
        $this->options['InboundShipmentHeader.ShipFromAddress.CountryCode'] = $a['CountryCode'];
        $this->options['InboundShipmentHeader.ShipFromAddress.PostalCode'] = $a['PostalCode'];
    }

    /**
     * Resets the address options.
     *
     * Since address is a required parameter, these options should not be removed
     * without replacing them, so this method is not public.
     */
    protected function resetAddress()
    {
        unset($this->options['InboundShipmentHeader.ShipFromAddress.Name']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.AddressLine1']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.AddressLine2']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.City']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.DistrictOrCounty']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.StateOrProvinceCode']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.CountryCode']);
        unset($this->options['InboundShipmentHeader.ShipFromAddress.PostalCode']);
    }

    /**
     * Sets the items. (Required)
     *
     * This method sets the Fulfillment Order ID to be sent in the next request.
     * This parameter is required for creating a fulfillment order with Amazon.
     * The array provided should contain a list of arrays, each with the following fields:
     * <ul>
     * <li><b>SellerSKU</b> - max: 50 char</li>
     * <li><b>Quantity</b> - numeric</li>
     * <li><b>QuantityInCase</b> (optional) - numeric</li>
     * </ul>
     * @param array $a <p>See above.</p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function setItems($a)
    {
        if (!$a || is_null($a) || is_string($a)) {
            $this->log("Tried to set Items to invalid values", 'Warning');
            return false;
        }
        $this->resetItems();
        $caseflag = false;
        $i = 1;
        foreach ($a as $x) {

            if (is_array($x) && array_key_exists('SellerSKU', $x) && array_key_exists('Quantity', $x)) {
                $this->options['InboundShipmentItems.member.' . $i . '.SellerSKU'] = $x['SellerSKU'];
                $this->options['InboundShipmentItems.member.' . $i . '.QuantityShipped'] = $x['Quantity'];
                if (array_key_exists('QuantityInCase', $x)) {
                    $this->options['InboundShipmentItems.member.' . $i . '.QuantityInCase'] = $x['QuantityInCase'];
                    $caseflag = true;
                }
                $i++;
            } else {
                $this->resetItems();
                $this->log("Tried to set Items with invalid array", 'Warning');
                return false;
            }
        }
        $this->setCases($caseflag);
    }

    /**
     * Resets the item options.
     *
     * Since the list of items is a required parameter, these options should not be removed
     * without replacing them, so this method is not public.
     */
    private function resetItems()
    {
        foreach ($this->options as $op => $junk) {
            if (preg_match("#InboundShipmentItems#", $op)) {
                unset($this->options[$op]);
            }
        }
    }

    /**
     * Sets the shipment status. (Required)
     * @param string $s <p>"WORKING", "SHIPPED", or "CANCELLED" (updating only)</p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function setStatus($s)
    {
        if (is_string($s) && $s) {
            if ($s == 'WORKING' || $s == 'SHIPPED' || $s == 'CANCELLED') {
                $this->options['InboundShipmentHeader.ShipmentStatus'] = $s;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Sets the shipment ID. (Required)
     * @param string $s <p>Shipment ID</p>
     * @return boolean <b>FALSE</b> if improper input
     */
    public function setShipmentId($s)
    {
        if (is_string($s) && $s) {
            $this->options['ShipmentId'] = $s;
        } else {
            return false;
        }
    }

    /**
     * Set whether or not cases are required. (Required if cases used)
     * @param boolean $b <p>Defaults to <b>TRUE</b>.</p>
     */
    protected function setCases($b = true)
    {
        if ($b) {
            $this->options['InboundShipmentHeader.AreCasesRequired'] = 'true';
        } else {
            $this->options['InboundShipmentHeader.AreCasesRequired'] = 'false';
        }
    }

    /**
     * Sends a request to Amazon to create an Inbound Shipment.
     *
     * Submits a <i>CreateInboundShipment</i> request to Amazon. In order to do this,
     * all parameters must be set. Data for these headers can be generated using an
     * <i>AmazonShipmentPlanner</i> object. Amazon will send back the Shipment ID
     * as a response, which can be retrieved using <i>getShipmentId</i>.
     * @return boolean <b>TRUE</b> if success, <b>FALSE</b> if something goes wrong
     */
    public function createShipment()
    {
        if (!isset($this->options['ShipmentId'])) {
            $this->log("Shipment ID must be set in order to create it", 'Warning');
            return false;
        }
        if (!array_key_exists('InboundShipmentHeader.ShipFromAddress.Name', $this->options)) {
            $this->log("Header must be set in order to make a shipment", 'Warning');
            return false;
        }
        if (!array_key_exists('InboundShipmentItems.member.1.SellerSKU', $this->options)) {
            $this->log("Items must be set in order to make a shipment", 'Warning');
            return false;
        }
        $this->options['Action'] = 'CreateInboundShipment';

        $url = $this->urlbase . $this->urlbranch;

        $query = $this->genQuery();

        $path = $this->options['Action'] . 'Result';
        if ($this->mockMode) {
            $xml = $this->fetchMockFile()->$path;
        } else {
            $response = $this->sendRequest($url, array('Post' => $query));

            if (!$this->checkResponse($response)) {
                return false;
            }

            $xml = simplexml_load_string($response['body'])->$path;
        }
        $this->shipmentId = (string)$xml->ShipmentId;

        if ($this->shipmentId) {
            $this->log("Successfully created Shipment #" . $this->shipmentId);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sends a request to Amazon to create an Inbound Shipment.
     *
     * Submits a <i>UpdateInboundShipment</i> request to Amazon. In order to do this,
     * all parameters must be set. Data for these headers can be generated using an
     * <i>AmazonShipmentPlanner</i> object. Amazon will send back the Shipment ID
     * as a response, which can be retrieved using <i>getShipmentId</i>.
     * @return boolean <b>TRUE</b> if success, <b>FALSE</b> if something goes wrong
     */
    public function updateShipment()
    {
        if (!isset($this->options['ShipmentId'])) {
            $this->log("Shipment ID must be set in order to update it", 'Warning');
            return false;
        }
        if (!array_key_exists('InboundShipmentHeader.ShipFromAddress.Name', $this->options)) {
            $this->log("Header must be set in order to update a shipment", 'Warning');
            return false;
        }
        if (!array_key_exists('InboundShipmentItems.member.1.SellerSKU', $this->options)) {
            $this->log("Items must be set in order to update a shipment", 'Warning');
            return false;
        }
        $this->options['Action'] = 'UpdateInboundShipment';

        $url = $this->urlbase . $this->urlbranch;

        $query = $this->genQuery();

        $path = $this->options['Action'] . 'Result';
        if ($this->mockMode) {
            $xml = $this->fetchMockFile()->$path;
        } else {
            $response = $this->sendRequest($url, array('Post' => $query));

            if (!$this->checkResponse($response)) {
                return false;
            }

            $xml = simplexml_load_string($response['body'])->$path;
        }
        $this->shipmentId = (string)$xml->ShipmentId;

        if ($this->shipmentId) {
            $this->log("Successfully updated Shipment #" . $this->shipmentId);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the shipment ID of the newly created/modified order.
     * @return string|boolean single value, or <b>FALSE</b> if Shipment ID not fetched yet
     */
    public function getShipmentId()
    {
        if (isset($this->shipmentId)) {
            return $this->shipmentId;
        } else {
            return false;
        }
    }

    /**
     * 获取箱唛pdf
     * User: Gerry
     * Date: 2019-11-06 14:20
     * @param array $data  ShipmentId,PageType,NumberOfPackages
     * @return array|bool
     * @throws Exception
     */
    public function getPackageLabels($data = array())
    {

        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }
        if(!isset($data['PageType']) || empty($data['PageType'])){
            $this->log("parameter : PageType error", 'Urgent');
            return false;
        }
        if(!isset($data['NumberOfPackages']) || empty($data['NumberOfPackages'])){
            $this->log("parameter : NumberOfPackages error", 'Urgent');
            return false;
        }

        $this->options['Action'] = 'GetPackageLabels';
        $this->options['ShipmentId'] = $data['ShipmentId'];
        $this->options['PageType'] = $data['PageType'];
        $this->options['NumberOfPackages'] = $data['NumberOfPackages'];
        try {
            $url = $this->urlbase . $this->urlbranch;
            $query = $this->genQuery();
            $path = $this->options['Action'] . 'Result';
            if ($this->mockMode) {
                $xml = $this->fetchMockFile()->$path;
            } else {
                $response = $this->sendRequest($url, array('Post' => $query));
                if (!$this->checkResponse($response)) {
                    return false;
                }

                $xml = simplexml_load_string($response['body'])->$path;
            }

            if (!$xml) {
                return false;
            }
            $pdf = [
                'PdfDocument' => (string)$xml->TransportDocument->PdfDocument,
                'Checksum' => (string)$xml->TransportDocument->Checksum,
            ];
            return $pdf;
        }catch (Exception $e) {
            throw new InvalidArgumentException('Get Package Label error.');
        }

    }


    /**
     * 设置货件运输信息
     * @param $a
     * @return bool
     * @throws \Exception
     */
    public function setTransportDetails($data = array())
    {
        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }

        if(!isset($data['IsPartnered'])){
            $this->log("parameter : IsPartnered error", 'Urgent');
            return false;
        }

        if(!isset($data['ShipmentType']) || empty($data['ShipmentType']) || !in_array($data['ShipmentType'],['SP','LTL'])){
            $this->log("parameter : ShipmentType error", 'Urgent');
            return false;
        }

        if($data['IsPartnered'] === 'true' && $data['ShipmentType'] == 'SP'){
            if (!isset($data['PackageList']) || empty($data['PackageList']) || !is_array($data['PackageList'])) {
                $this->log("Tried to set PackageList list to invalid values", 'Warning');
                return false;
            }
            $i = 1;

            foreach ($data['PackageList'] as $x){
                if (is_array($x)  && array_key_exists('Length', $x) && array_key_exists('Width', $x) && array_key_exists('Height', $x)
                    && array_key_exists('DimensionsUnit', $x)
                    && array_key_exists('WeightUnit', $x) && array_key_exists('WeightValue', $x)) {
                    $this->options['TransportDetails.PartneredSmallParcelData.PackageList.member.' . $i . '.Dimensions.Length'] = $x['Length'];
                    $this->options['TransportDetails.PartneredSmallParcelData.PackageList.member.' . $i . '.Dimensions.Width'] = $x['Width'];
                    $this->options['TransportDetails.PartneredSmallParcelData.PackageList.member.' . $i . '.Dimensions.Height'] = $x['Height'];
                    $this->options['TransportDetails.PartneredSmallParcelData.PackageList.member.' . $i . '.Dimensions.Unit'] = $x['DimensionsUnit'];
                    $this->options['TransportDetails.PartneredSmallParcelData.PackageList.member.' . $i . '.Weight.Unit'] = $x['WeightUnit'];
                    $this->options['TransportDetails.PartneredSmallParcelData.PackageList.member.' . $i . '.Weight.Value'] = $x['WeightValue'];
                    $i++;
                } else {
                    $this->log("Tried to set PackageList with invalid array", 'Warning');
                    return false;
                }
            }
        }elseif ($data['IsPartnered'] === 'false' && $data['ShipmentType'] == 'SP'){
            if(!isset($data['CarrierName']) || empty($data['CarrierName'])){
                $this->log("parameter : CarrierName error", 'Warning');
                return false;
            }
            $this->options['TransportDetails.NonPartneredSmallParcelData.CarrierName'] = $data['CarrierName'];
            if (!isset($data['PackageList']) || empty($data['PackageList']) || !is_array($data['PackageList'])) {
                $this->log("Tried to set package list to invalid values", 'Warning');
                return false;
            }
            $i = 1;
            foreach ($data['PackageList'] as $x){
                if (is_array($x) && array_key_exists('TrackingId', $x)) {
                    $this->options['TransportDetails.NonPartneredSmallParcelData.PackageList.member.' . $i . '.TrackingId'] = $x['TrackingId'];
                    $i++;
                } else {
                    $this->log("Tried to set PackageList with invalid array", 'Warning');
                    return false;
                }
            }
        }elseif ($data['IsPartnered'] === 'true' && $data['ShipmentType'] == 'LTL'){
            if (is_array($data) && array_key_exists('Contact', $data) && array_key_exists('BoxCount', $data) && array_key_exists('FreightReadyDate', $data)) {
                if(is_array($data['Contact'])
                    && array_key_exists('Name', $data['Contact'])
                    && array_key_exists('Phone', $data['Contact'])
                    && array_key_exists('Email', $data['Contact'])
                    && array_key_exists('Fax', $data['Contact'])){
                }else{
                    $this->log("Tried to set PartneredLtlData with invalid Contact array", 'Warning');
                    return false;
                }
                $this->options['TransportDetails.PartneredLtlData.Contact.Name'] = $data['Contact']['Name'];
                $this->options['TransportDetails.PartneredLtlData.Contact.Phone'] = $data['Contact']['Phone'];
                $this->options['TransportDetails.PartneredLtlData.Contact.Email'] = $data['Contact']['Email'];
                $this->options['TransportDetails.PartneredLtlData.Contact.Fax'] = $data['Contact']['Fax'];
                $this->options['TransportDetails.PartneredLtlData.BoxCount'] = $data['BoxCount'];
                $this->options['TransportDetails.PartneredLtlData.FreightReadyDate'] = $data['FreightReadyDate'];

                if (isset($data['PalletList']) && !empty($data['PalletList']) && is_array($data['PalletList'])) {
                    $i = 1;
                    foreach ($data['PalletList'] as $x){
                        if (is_array($x)  && array_key_exists('Length', $x)
                            && array_key_exists('Width', $x)
                            && array_key_exists('Height', $x)
                            && array_key_exists('DimensionsUnit', $x)
                            && array_key_exists('WeightUnit', $x)
                            && array_key_exists('WeightValue', $x)
                            && array_key_exists('IsStacked', $x)) {
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.Dimensions.Length'] = $x['Length'];
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.Dimensions.Width'] = $x['Width'];
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.Dimensions.Height'] = $x['Height'];
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.Dimensions.Unit'] = $x['DimensionsUnit'];
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.Weight.Unit'] = $x['WeightUnit'];
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.Weight.Value'] = $x['WeightValue'];
                            $this->options['TransportDetails.PartneredLtlData.PalletList.member.' . $i . '.IsStacked'] = $x['IsStacked'];
                            $i++;
                        } else {
                            $this->log("Tried to set PalletList with invalid array", 'Warning');
                            return false;
                        }
                    }
                }
            } else {
                $this->log("Tried to set PartneredLtlData with invalid array", 'Warning');
                return false;
            }
        }elseif ($data['IsPartnered'] === 'false' && $data['ShipmentType'] == 'LTL'){
            if (is_array($data) && array_key_exists('CarrierName', $data) && array_key_exists('ProNumber', $data)) {
                $this->options['TransportDetails.NonPartneredLtlData.CarrierName'] = $data['CarrierName'];
                $this->options['TransportDetails.NonPartneredLtlData.ProNumber'] = $data['ProNumber'];
            } else {
                $this->log("Tried to set NonPartneredLtlData with invalid array", 'Warning');
                return false;
            }
        }
    }

    /**
     * 向亚马逊发送入库货件的运输信息。
     * @param array $data
     * @return bool|\SimpleXMLElement
     * @throws \Exception
     */
    public function putTransportContent($data = array())
    {
        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }

        if(!isset($data['IsPartnered'])){
            $this->log("parameter : IsPartnered error", 'Urgent');
            return false;
        }

        if(!isset($data['ShipmentType']) || empty($data['ShipmentType']) || !in_array($data['ShipmentType'],['SP','LTL'])){
            $this->log("parameter : ShipmentType error", 'Urgent');
            return false;
        }

        if(!isset($data['ShipmentType']) || empty($data['ShipmentType']) || !in_array($data['ShipmentType'],['SP','LTL'])){
            $this->log("parameter : ShipmentType error", 'Urgent');
            return false;
        }

        if($data['IsPartnered'] === 'true' && $data['ShipmentType'] == 'SP'){
            if (!array_key_exists('TransportDetails.PartneredSmallParcelData.PackageList.member.1.Weight.Unit', $this->options)) {
                $this->log("parameter : TransportDetails error", 'Urgent');
                return false;
            }
        }elseif ($data['IsPartnered'] === 'false' && $data['ShipmentType'] == 'SP'){
            if (!array_key_exists('TransportDetails.NonPartneredSmallParcelData.PackageList.member.1.TrackingId', $this->options)) {
                $this->log("parameter : TransportDetails error", 'Urgent');
                return false;
            }
        }elseif ($data['IsPartnered'] === 'true' && $data['ShipmentType'] == 'LTL'){
            if (!array_key_exists('TransportDetails.PartneredLtlData.Contact.Name', $this->options)) {
                $this->log("parameter : TransportDetails error", 'Urgent');
                return false;
            }
        }elseif ($data['IsPartnered'] === 'false' && $data['ShipmentType'] == 'LTL'){
            if (!array_key_exists('TransportDetails.NonPartneredLtlData.CarrierName', $this->options)) {
                $this->log("parameter : TransportDetails error", 'Urgent');
                return false;
            }
        }

        $this->options['ShipmentId'] = $data['ShipmentId'];
        $this->options['IsPartnered'] = $data['IsPartnered'];
        $this->options['ShipmentType'] = $data['ShipmentType'];
        $this->options['Action'] = 'PutTransportContent';
        try {
            $url = $this->urlbase . $this->urlbranch;
            $query = $this->genQuery();
            $path = $this->options['Action'] . 'Result';
            if ($this->mockMode) {
                $xml = $this->fetchMockFile()->$path;
            } else {
                $response = $this->sendRequest($url, array('Post' => $query));
                if (!$this->checkResponse($response)) {
                    return false;
                }

                $xml = simplexml_load_string($response['body'])->$path;
            }

            if (!$xml) {
                return false;
            }

            return true;
        }catch (Exception $e) {
            throw new InvalidArgumentException('Put Shipment Transport Content error.');
        }

    }


    /**
     * 预估由亚马逊合作承运人配送的入库货件的运费
     * @param array $data
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function estimateTransport($data = array())
    {
        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }
        $this->options['Action'] = 'EstimateTransportRequest';
        $this->options['ShipmentId'] = $data['ShipmentId'];
        try {
            $url = $this->urlbase . $this->urlbranch;
            $query = $this->genQuery();
            $path = $this->options['Action'] . 'Result';
            if ($this->mockMode) {
                $xml = $this->fetchMockFile()->$path;
            } else {
                $response = $this->sendRequest($url, array('Post' => $query));
                if (!$this->checkResponse($response)) {
                    return false;
                }

                $xml = simplexml_load_string($response['body'])->$path;
            }

            if (!$xml) {
                return false;
            }

            return true;
        }catch (Exception $e) {
            throw new InvalidArgumentException('Estimate Transport error.');
        }

    }

    /**
     * 获取包裹运输信息
     * @param array $data
     * @return xml
     * @throws \Exception
     */
    public function getTransportContent($data = array())
    {
        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }
        $this->options['Action'] = 'GetTransportContent';
        $this->options['ShipmentId'] = $data['ShipmentId'];
        try {
            $url = $this->urlbase . $this->urlbranch;
            $query = $this->genQuery();
            $path = $this->options['Action'] . 'Result';
            if ($this->mockMode) {
                $xml = $this->fetchMockFile()->$path;
            } else {
                $response = $this->sendRequest($url, array('Post' => $query));
                if (!$this->checkResponse($response)) {
                    return false;
                }

                $xml = simplexml_load_string($response['body'])->$path;
            }

            if (!$xml) {
                return false;
            }

            $data = [];
            if(isset($xml->TransportContent->TransportHeader)){
                $data['TransportContent']['TransportHeader']['SellerId'] = (string)$xml->TransportContent->TransportHeader->SellerId;
                $data['TransportContent']['TransportHeader']['IsPartnered'] = (string)$xml->TransportContent->TransportHeader->IsPartnered;
                $data['TransportContent']['TransportHeader']['ShipmentId'] = (string)$xml->TransportContent->TransportHeader->ShipmentId;
                $data['TransportContent']['TransportHeader']['ShipmentType'] = (string)$xml->TransportContent->TransportHeader->ShipmentType;
            }
            if(isset($xml->TransportContent->TransportResult)){
                $data['TransportContent']['TransportResult']['TransportStatus'] = (string)$xml->TransportContent->TransportResult->TransportStatus;
            }
            if(isset($xml->TransportContent->TransportDetails)){
                $transportDetails = $xml->TransportContent->TransportDetails;

                if(isset($transportDetails->PartneredSmallParcelData)){
                    foreach ($transportDetails->PartneredSmallParcelData->PackageList->children() as $p){
                        $packageList = [
                            'TrackingId' => (string)$p->TrackingId,
                            'PackageStatus' => (string)$p->PackageStatus,
                            'CarrierName' => (string)$p->CarrierName,
                        ] ;
                        $packageList['Dimensions'] = [
                            'Unit' => (string)$p->Dimensions->Unit,
                            'Length' => (string)$p->Dimensions->Length,
                            'Width' => (string)$p->Dimensions->Width,
                            'Height' => (string)$p->Dimensions->Height,
                        ];
                        $packageList['Weight'] = [
                            'Unit' => (string)$p->Weight->Unit,
                            'Value' => (string)$p->Weight->Value,
                        ];
                        $data['TransportContent']['TransportDetails']['PartneredSmallParcelData']['PackageList'] = $packageList;
                    }
                    $data['TransportContent']['TransportDetails']['PartneredSmallParcelData']['PartneredEstimate'] = [
                        'CurrencyCode' => (string)$transportDetails->PartneredSmallParcelData->PartneredEstimate->Amount->CurrencyCode,
                        'Value' => (string)$transportDetails->PartneredSmallParcelData->PartneredEstimate->Amount->Value,
                    ];
                }

                if(isset($transportDetails->NonPartneredSmallParcelData)){
                    foreach ($transportDetails->NonPartneredSmallParcelData->PackageList->children() as $p){
                        $data['TransportContent']['TransportDetails']['NonPartneredSmallParcelData']['PackageList'] = [
                            'CarrierName' => (string)$p->CarrierName,
                            'PackageStatus' => (string)$p->PackageStatus,
                            'TrackingId' => (string)$p->TrackingId,
                        ] ;
                    }
                }

                if(isset($transportDetails->PartneredLtlData)){
                    $data['TransportContent']['TransportDetails']['PartneredLtlData']['Contact'] = (string)$transportDetails->PartneredLtlData->Contact;
                    $data['TransportContent']['TransportDetails']['PartneredLtlData']['BoxCount'] = (string)$transportDetails->PartneredLtlData->BoxCount;
                    $data['TransportContent']['TransportDetails']['PartneredLtlData']['FreightReadyDate'] = (string)$transportDetails->PartneredLtlData->FreightReadyDate;
                }

                if(isset($transportDetails->NonPartneredLtlData)){
                    $data['TransportContent']['TransportDetails']['NonPartneredLtlData']['Contact'] = (string)$transportDetails->NonPartneredLtlData->CarrierName;
                    $data['TransportContent']['TransportDetails']['NonPartneredLtlData']['ProNumber'] = (string)$transportDetails->NonPartneredLtlData->ProNumber;
                }
            }
            return $data;
        }catch (Exception $e) {
            throw new InvalidArgumentException('Get Shipment Transport Content error.');
        }

    }

    /**
     * 取消发货运输信息
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function voidTransport($data = array())
    {
        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }
        $this->options['Action'] = 'VoidTransportRequest';
        $this->options['ShipmentId'] = $data['ShipmentId'];
        try {
            $url = $this->urlbase . $this->urlbranch;
            $query = $this->genQuery();
            $path = $this->options['Action'] . 'Result';
            if ($this->mockMode) {
                $xml = $this->fetchMockFile()->$path;
            } else {
                $response = $this->sendRequest($url, array('Post' => $query));
                if (!$this->checkResponse($response)) {
                    return false;
                }

                $xml = simplexml_load_string($response['body'])->$path;
            }

            if (!$xml) {
                return false;
            }

            return true;
        }catch (Exception $e) {
            throw new InvalidArgumentException('Void shipment error.');
        }

    }

    /**
     * 确认发货
     * @param array $data
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function confirmShipment($data = array())
    {
        if(!isset($data['ShipmentId']) || empty($data['ShipmentId'])){
            $this->log("parameter : ShipmentId error", 'Urgent');
            return false;
        }
        $this->options['Action'] = 'ConfirmTransportRequest';
        $this->options['ShipmentId'] = $data['ShipmentId'];
        try {
            $url = $this->urlbase . $this->urlbranch;
            $query = $this->genQuery();
            $path = $this->options['Action'] . 'Result';
            if ($this->mockMode) {
                $xml = $this->fetchMockFile()->$path;
            } else {
                $response = $this->sendRequest($url, array('Post' => $query));
                if (!$this->checkResponse($response)) {
                    return false;
                }

                $xml = simplexml_load_string($response['body'])->$path;
            }

            if (!$xml) {
                return false;
            }

            return true;
        }catch (Exception $e) {
            throw new InvalidArgumentException('Confirm shipment error.');
        }

    }


}

?>
