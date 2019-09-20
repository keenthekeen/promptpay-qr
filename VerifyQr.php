<?php

namespace PromptpayQr;

/**
 * Verification QR Parser
 *
 * @package   promptpay-qr
 * @author    Siwat Techavoranant <keen@keendev.net>
 * @copyright 2019 Siwat Techavoranant
 */
class VerifyQr {
    protected $qrContent;
    protected $payload;
    public $data = [];
    public $apiId = [];
    public $sendingBankId;
    public $transactionId;
    
    public function __construct(string $qrContent, bool $skipCheck = false) {
        $this->qrContent = $qrContent;
        
        while ($this->getRemainingLength() > 4) {
            $newData = [
                'tag' => $this->popFromBeginning(2),
                'length' => $this->popFromBeginning(2)
            ];
            $newData['content'] = $this->popFromBeginning($newData['length']);
            $this->data[$newData['tag']] = $newData;
            
            if ($newData['tag'] === '00') {
                $this->payload = $newData['content'];
                for ($k = 0 ; $k < 3; $k++) {
                    switch ($this->popPayloadFromBeginning(2)) {
                        case '00':
                            $this->apiId = $this->popPayloadFromBeginning((int) $this->popPayloadFromBeginning(2));
                            break;
                        case '01':
                            $this->sendingBankId = $this->popPayloadFromBeginning((int) $this->popPayloadFromBeginning(2));
                            break;
                        case '02':
                            $this->transactionId = $this->popPayloadFromBeginning((int) $this->popPayloadFromBeginning(2));
                    }
                }
            }
        }
        
        if ($this->getRemainingLength() > 0) {
            throw new \Exception('Invalid QR content');
        }
        
        // verify checksum and locale
        if (!$skipCheck) {
            if ($this->data['51']['content'] != 'TH') {
                throw new \Exception('Country verification failed');
            } elseif (Helper::crc16(substr($qrContent, 0, -4)) != $this->data['91']['content']) {
                throw new \Exception('Checksum failed');
            }
        }
        
        return $this;
    }
    
    protected function popFromBeginning(int $length) {
        $v = substr($this->qrContent, 0, $length);
        $this->qrContent = substr($this->qrContent, $length);
        
        return $v;
    }
    
    protected function popPayloadFromBeginning(int $length) {
        $v = substr($this->payload, 0, $length);
        $this->payload = substr($this->payload, $length);
        
        return $v;
    }
    
    protected function getRemainingLength() {
        return strlen($this->qrContent);
    }
}
