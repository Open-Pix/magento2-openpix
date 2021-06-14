<?php

namespace OpenPix\Pix\Api\Data\PixTransaction;

interface PixTransactionInterface {
    /**
     * Return the charge.
     *
     * @return \OpenPix\Pix\Api\Data\OpenPixChargeInterface Charge.
     */
    public function getCharge();

    /**
     * Set the charge
     *
     * @param \OpenPix\Pix\Api\Data\OpenPixChargeInterface $charge
     * @return $this
     */
    public function setCharge($charge);

    /**
     * Return the customer.
     *
     * @return \OpenPix\Pix\Api\Data\OpenPixCustomerInterface Customer.
     */
    public function getCustomer();

    /**
     * Set the customer
     *
     * @param \OpenPix\Pix\Api\Data\OpenPixCustomerInterface $customer
     * @return $this
     */
    public function setCustomer($customer);

    /**
     * Return the time.
     *
     * @return string Time.
     */
    public function getTime();

    /**
     * Set the time.
     *
     * @param string $time
     * @return $this
     */
    public function setTime($time);

    /**
     * Return the value.
     *
     * @return int Value.
     */
    public function getValue();

    /**
     * Set the value.
     *
     * @param string $value
     * @return $this
     */
    public function setValue($value);

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
     * Return the infoPagador.
     *
     * @return string InfoPagador.
     */
    public function getInfoPagador();

    /**
     * Set the infoPagador.
     *
     * @param string $infoPagador
     * @return $this
     */
    public function setInfoPagador($infoPagador);

    // @todo implement raw getter and setter
}
