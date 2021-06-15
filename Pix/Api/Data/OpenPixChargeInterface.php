<?php

namespace OpenPix\Pix\Api\Data;

interface OpenPixChargeInterface {
    /**
     * Return the status of this charge.
     *
     * @return string Charge Status.
     */
    public function getStatus();

    /**
     * Set the status of this charge.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Return the correlationID.
     *
     * @return string CorrelationID.
     */
    public function getCorrelationID();

    /**
     * Set the correlationID
     *
     * @param string $correlationID
     * @return $this
     */
    public function setCorrelationID($correlationID);

    /**
     * Return the transactionID.
     *
     * @return string TransactionID.
     */
    public function getTransactionID();

    /**
     * Set the transactionID
     *
     * @param string $transactionID
     * @return $this
     */
    public function setTransactionID($transactionID);

    /**
     * Return the brCode.
     *
     * @return string BrCode.
     */
    public function getBrCode();

    /**
     * Set the brCode
     *
     * @param string $brCode
     * @return $this
     */
    public function setBrCode($brCode);

    /**
     * Return the createdAt.
     *
     * @return string CreatedAt.
     */
    public function getCreatedAt();

    /**
     * Set the createdAt
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Return the updatedAt.
     *
     * @return string UpdatedAt.
     */
    public function getUpdatedAt();

    /**
     * Set the updatedAt
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Return the customer.
     *
     * @return \OpenPix\Pix\Api\Data\OpenPixCustomerInterface|null Customer.
     */
    public function getCustomer();

    /**
     * Set the customer or null
     *
     * @param \OpenPix\Pix\Api\Data\OpenPixCustomerInterface|null $customer
     * @return $this
     */
    public function setCustomer($customer);
}
