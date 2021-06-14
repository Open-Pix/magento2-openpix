<?php

namespace OpenPix\Pix\Api\Data;

interface OpenPixCustomerInterface {
    /**
     * Return the name of this customer.
     *
     * @return string Name.
     */
    public function getName();

    /**
     * Set the name of this customer.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Return the email of this customer.
     *
     * @return string Email.
     */
    public function getEmail();

    /**
     * Set the email of this customer.
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * Return the phone of this customer.
     *
     * @return string Phone.
     */
    public function getPhone();

    /**
     * Set the phone of this customer.
     *
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone);

    // @todo implement taxID getter and setter

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
     * Return the taxID.
     *
     * @return \OpenPix\Pix\Api\Data\OpenPixTaxIDInterface TaxID.
     */
    public function getTaxID();

    /**
     * Set the taxID
     *
     * @param \OpenPix\Pix\Api\Data\OpenPixTaxIDInterface $taxID
     * @return $this
     */
    public function setTaxID($taxID);
}
