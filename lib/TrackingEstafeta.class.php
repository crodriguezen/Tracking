<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
class TrackingEstafeta {
    public static $URL_SEARCH_BY_GET = "https://cs.estafeta.com/es/Tracking/searchByGet";
    public static $URL_SIGNATURE = "https://cs.estafeta.com/es/Tracking/GetTrackingSignatureBase64";
    public static $URL_DELIVERY_PROOF = "https://cs.estafeta.com/es/Tracking/GetTrackingPODPDFBase64";
    public static $URL_HISTORY = "https://cs.estafeta.com/es/Tracking/GetTrackingItemHistory";

    private $guzzleCookieJar = null;
    private $guzzleClient = null;
    private $isInit = false;

    private $scrapingWayBill = "";
    private $scrapingTrackingNumber = "";
    private $scrapingServiceType = "";
    private $scrapingScheduledDeliveryDate = "";
    private $scrapingAddressee = "";

    private $wayBill = "";
    private $wayBillType = "0";
    private $isShipmentDetail = "True";

    private $lastException = "";

    public function __construct($wayBill) {
        $this->wayBill = $wayBill;
        $this->guzzleCookieJar = new \GuzzleHttp\Cookie\CookieJar();
        $this->guzzleClient = new GuzzleHttp\Client([
            'cookies' => true,
            'headers' => [
                'Accept'=> '*/*',
                'Accept-Encoding'=> 'gzip, deflate, br',
                'Accept-Language'=> 'es-MX,es;q=0.8,en-US;q=0.5,en;q=0.3',
                'Connection'=> 'keep-alive',
                'Host'=> 'https://cs.estafeta.com/',
                'Origin'=> 'https://cs.estafeta.com/',
                'Referer'=> 'https://cs.estafeta.com/es/Tracking/searchByGet?wayBill=' . $wayBill . '&wayBillType=0&isShipmentDetail=True',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0',
                'Cookie'=> '__cfduid=XXXXXX; optimizelyEndUserId=oeu1498769689109r0.32368438346411443; optimizelySegments=%7XX; _vis_opt_test_cookie=1; _vwo_uuid=AF15229BDFDAB8BA12asdasB9561E984147AE; _vis_opt_exp_163_combi=2; optimizelyPendingLogEvents=%5B%22n%3Dhttps%253asdasdA%252F%252Ftools.XXXXX.com%252F%26u%3Doeu1498769689109r0.32368438346411443%26wxhr%3Dtrue%26time%3D1asd501859409.619%26asd345435f%3D8430845asd915%26g%3D%22%5D'
            ]
        ]);
    }

    public function setWayBill($wayBill) {
        $this->wayBill = $wayBill;
    }

    public function getTrackingDetails() {
        $response = $this->guzzleClient->get(TrackingEstafeta::$URL_SEARCH_BY_GET . "?wayBill=" . $this->wayBill . "&wayBillType=" . $this->wayBillType . "&isShipmentDetail=" . $this->isShipmentDetail);
        $html = $response->getBody()->getContents();

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXpath($document);

        $shipmentInfoDiv = $xpath->query("//div[@class='shipmentInfoDiv']");
        if ($shipmentInfoDiv->length == 0) {
            throw new Exception('shipmentInfoDiv - not present for scraping.');
        } else {
            $arrShipmentInfoSeparator = $xpath->query(".//div[@class='fontBold']", $shipmentInfoDiv->item(0));
            $this->scrapingWayBill = $arrShipmentInfoSeparator[0]->nodeValue;
            $this->scrapingTrackingNumber = $arrShipmentInfoSeparator[1]->nodeValue;
            $this->scrapingServiceType = $arrShipmentInfoSeparator[2]->nodeValue;
            $this->scrapingScheduledDeliveryDate = $arrShipmentInfoSeparator[3]->nodeValue;

            $arrShipmentInfoSeparator = $xpath->query(".//span[@class='fontBold']", $shipmentInfoDiv->item(1));
            $this->scrapingAddressee = $arrShipmentInfoSeparator[0]->nodeValue;

        }

        $json = json_encode(array(
            "company" => "Estafeta",
            "wayBill" => $this->scrapingWayBill,
            "trackingNumber" => $this->scrapingTrackingNumber,
            "serviceType" => $this->scrapingServiceType,
            "scheduledDeliveryDate" => $this->scrapingScheduledDeliveryDate,
            "addressee" => $this->scrapingAddressee
        ));
        return json_decode($json);
    }

    public function getSignature() {
        $signatureImageB64 = "";
        $success = true;

        try {
            $signatureResponse = $this->guzzleClient->post(
                TrackingEstafeta::$URL_SIGNATURE,
                array(
                    'form_params' => array(
                        'shipmentIndex' => $this->wayBill,
                        'signatureId' => $this->wayBill
                    )
                )
            )->getBody()->getContents();

            $documentImage = new DOMDocument;
            @$documentImage->loadHTML($signatureResponse);
            $xpathImage = new DOMXpath($documentImage);

            $signatureImageB64 = $xpathImage->query("//div//img/@src")[0]->nodeValue;
        } catch(Exception $e) {
            $success = false;
        }

        $json = json_encode(array(
            "success" => $success,
            "wayBill" => $this->wayBill,
            "type" => "image",
            "base64" => $signatureImageB64
        ));
        return json_decode($json);
    }

    public function getDeliveryProof() {
        $deliveryProofB64 = "";
        $success = true;

        try {
            $deliveryProofResponse = $this->guzzleClient->post(
                TrackingEstafeta::$URL_DELIVERY_PROOF,
                array(
                    'form_params' => array(
                        'shipmentIndex' => $this->wayBill
                    )
                )
            )->getBody()->getContents();
            $documentDeliveryProof = new DOMDocument;
            @$documentDeliveryProof->loadHTML($deliveryProofResponse);
            $xpathDeliveryProof = new DOMXpath($documentDeliveryProof);
        
            $deliveryProofB64 = $xpathDeliveryProof->query("//object/@data")[0]->nodeValue;
        } catch(Exception $e) {
            $success = false;
        }

        $json = json_encode(array(
            "success" => $success,
            "wayBill" => $this->wayBill,
            "type" => "pdf",
            "base64" => $deliveryProofB64
        ));
        return json_decode($json);
    }

    public function getHistory() {
        $success = false;
        $message = "";
        $httpCode = "";
        $history = null;

        $date = "";
        $time = "";
        $place = "";
        $status = "";
        
        $historyResponse = null;
        try {
            $historyResponse = $this->guzzleClient->post(
                TrackingEstafeta::$URL_HISTORY,
                array(
                    'form_params' => array(
                        'wayBill' => $this->wayBill
                    )
                )
            );
            $httpCode = $historyResponse->getStatusCode();
            $html = $historyResponse->getBody()->getContents();

            $document = new DOMDocument;
            @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new DOMXpath($document);

            if ($xpath != null) {
                $history = array();
                $arrVerticalDottedLine = $xpath->query("//div[@class='vertical_dotted_line']");

                if ($arrVerticalDottedLine->length == 0) {
                    throw new Exception('vertical_dotted_line - not present for scraping.');
                }
                
                foreach($arrVerticalDottedLine as $vdl) { 
                    $divNew = $xpath->query(".//div[@class='new']", $vdl);
                    if ($divNew->length == 0) {
                        throw new Exception('div.new not present for scraping.');
                    }

                    $divRow = $xpath->query(".//div[@class='row historyEventRow  cursorPointer']", $divNew->item(0));
                    if ($divRow->length == 0) {
                        throw new Exception('div.row historyEventRow  cursorPointer - not present for scraping.');
                    }

                    // Column 2 - Node: Date
                    $col2 = $xpath->query(".//div[@class='col-xs-2 ']", $divRow->item(0));
                    if ($col2->length == 0) {
                        throw new Exception('div.col-xs-2 - not present for scraping.');
                    }
                    $date = $col2[0]->nodeValue;

                    // Column 3 - Node: History
                    $col3 = $xpath->query(".//div[@class='col-xs-9']", $divRow->item(0));
                    if ($col3->length == 0) {
                        throw new Exception('div.col-xs-9 - not present for scraping.');
                    }

                    // Colum 3 First Row - Node: History Main Event
                    $col3d1 = $xpath->query(".//div[@class='row eventInfo']", $col3->item(1));
                    $col3d1Divs = $xpath->query(".//div", $col3d1->item(0));

                    $time = $col3d1Divs[0]->nodeValue;
                    $place = $col3d1Divs[1]->nodeValue;
                    $status = $col3d1Divs[2]->nodeValue;

                    $history[] = array(
                        "date" => $date,
                        "time" => $time,
                        "place" => $place,
                        "status" => $status,
                        "main" => true
                    );

                    // Colum 3 Second Row - Node: History Secondary Events
                    $col3d2 = $xpath->query(".//div[@class='collapse remainingHistoryEvents']", $col3->item(0));
                    $col3d2Divs = $xpath->query(".//div[@class='row eventInfo']", $col3d2->item(0));
                    foreach ($col3d2Divs as $div) {
                        $d = $xpath->query(".//div", $div);
                        $history[] = array(
                            "date" => $date,
                            "time" => $d[0]->nodeValue,
                            "place" => $d[1]->nodeValue,
                            "status" => $d[2]->nodeValue,
                            "main" => false
                        );
                    }

                }

            }
        } catch(Exception $e) {
            $this->lastException = $e->getMessage();
            $message = $e->getMessage();
            if ($historyResponse != null) {
                $httpCode = $historyResponse->getStatusCode();
            }
            $success = false;
        }

        if ($history != null) {
            $success = true;
        }

        $json = json_encode(array(
            "success" => $success,
            "message" => $message,
            "httpCode" => $httpCode,
            "wayBill" => $this->wayBill,
            "history" => $history
        ));
        return json_decode($json);
    }

}
?>