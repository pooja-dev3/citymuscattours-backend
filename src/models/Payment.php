<?php

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel {
    protected $table = 'payments';
    protected $primaryKey = 'id';

    public function findByBooking($bookingId) {
        return $this->findOne(['booking_id' => $bookingId]);
    }

    public function createPayment($data) {
        return $this->create($data);
    }

    public function updatePayment($id, $data) {
        return $this->update($id, $data);
    }

    public function findPaymentById($id) {
        return $this->findById($id);
    }

    public function findPayment($conditions) {
        return $this->findOne($conditions);
    }
}

