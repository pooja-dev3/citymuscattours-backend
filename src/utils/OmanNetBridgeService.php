<?php
// VERSION: 2026-03-03_v10
/**
 * OmanNet Payment Gateway Service (JavaBridge / iPayPipe Version)
 */

define("JAVA_HOSTS", "127.0.0.1:8085");
require_once __DIR__ . "/../../PhpPlugin/JavaBridge/java/Java.inc";

class OmanNetBridgeService
{
    private $resourcePath;
    private $aliasName;
    private $currency;
    private $language;
    private $action;
    private $receiptURL;
    private $errorURL;
    private $isTestMode;

    public function __construct()
    {
        $config = Env::get();
        $this->resourcePath         = $config['omanNet']['resourcePath'] ?? '';
        $this->aliasName            = $config['omanNet']['alias']        ?? 'HALADOC';
        $this->currency             = $config['omanNet']['currency']     ?? '512';
        $this->language             = $config['omanNet']['language']     ?? 'USA';
        $this->action               = $config['omanNet']['action']       ?? '1';
        $this->receiptURL           = $config['omanNet']['receiptURL']   ?? '';
        $this->errorURL             = $config['omanNet']['errorURL']     ?? '';
        $this->isTestMode           = ($config['nodeEnv'] ?? 'development') !== 'production';

        if (empty($this->resourcePath)) {
            $this->resourcePath = realpath(__DIR__ . "/../../PhpPlugin/iPAYPlugin");
        }
    }

    public function generateTrackId($bookingId)
    {
        return str_pad($bookingId, 8, '0', STR_PAD_LEFT) . substr(time(), -10);
    }

    public function createPaymentRequest($data)
    {
        try {
            $orderId    = $data['orderId']    ?? $this->generateTrackId(time());
            $amount     = $data['amount']     ?? 0;
            $currencyCode = $this->mapCurrencyCode($data['currency'] ?? $this->currency);
            $formattedAmount = number_format((float)$amount, 3, '.', '');

            $myObj = new Java("com.fss.plugin.iPayPipe");
            $myObj->setResourcePath(trim($this->resourcePath));
            $myObj->setKeystorePath(trim($this->resourcePath));
            $myObj->setAlias(trim($this->aliasName));
            $myObj->setAction(trim($this->action));
            $myObj->setCurrency(trim($currencyCode));
            $myObj->setLanguage(trim($this->language));
            $myObj->setResponseURL(trim($data['returnUrl'] ?? $this->receiptURL));
            $myObj->setErrorURL(trim($data['errorUrl'] ?? $this->errorURL));
            $myObj->setAmt($formattedAmount);
            $myObj->setTrackId($orderId);
            $myObj->setUdf1(""); $myObj->setUdf2(""); $myObj->setUdf3(""); $myObj->setUdf4(""); $myObj->setUdf5("");

            $val = java_values($myObj->performPaymentInitializationHTTP());

            if ((string)$val !== "0") {
                $errorMsg = (string)$myObj->getError();
                throw new Exception("iPayPipe Initialization Failed (Code: $val). Error: $errorMsg");
            }

            $redirectUrl = (string)$myObj->getWebAddress();
            $paymentId   = (string)$myObj->getPaymentId();

            return [
                'success'     => true,
                'orderId'     => $orderId,
                'paymentPage' => [
                    'action'    => $redirectUrl,
                    'paymentId' => $paymentId,
                ],
            ];
        } catch (Exception $e) {
            error_log('OmanNet Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function processResponse($encryptedTrandata)
    {
        try {
            $myObj = new Java("com.fss.plugin.iPayPipe");
            $myObj->setResourcePath(trim($this->resourcePath));
            $myObj->setKeystorePath(trim($this->resourcePath));
            $myObj->setAlias(trim($this->aliasName));

            $val = java_values($myObj->parseEncryptedRequest($encryptedTrandata));
            if ((string)$val !== "0") {
                throw new Exception("iPayPipe Decryption Failed (Code: $val). Error: " . $myObj->getError());
            }

            return [
                'success'      => ((string)$myObj->getResult() === 'CAPTURED' || (string)$myObj->getResult() === 'APPROVED'),
                'result'       => (string)$myObj->getResult(),
                'trackid'      => (string)$myObj->getTrackId(),
                'amt'          => (string)$myObj->getAmt(),
                'paymentid'    => (string)$myObj->getPaymentId(),
                'error_text'   => (string)$myObj->getErrorText(),
                'raw_response' => 'JavaBridge Parsed Response',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verifyPaymentResponse($responseData)
    {
        return $this->processResponse($responseData['trandata'] ?? ($responseData[0] ?? ''));
    }

    private function mapCurrencyCode($currency)
    {
        $map = ['OMR' => '512', 'USD' => '840', 'INR' => '356', 'EUR' => '978', 'GBP' => '826'];
        return $map[strtoupper(trim($currency))] ?? $currency;
    }
}
