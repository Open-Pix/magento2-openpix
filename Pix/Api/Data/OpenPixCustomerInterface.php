<?php

namespace OpenPix\Pix\Api\Data;

interface OpenPixCustomerInterface {
    /**
     * Return the name of this customer.
     *
     * @return string Name.
     */
    public function getCustomer();

    /**
     * Set the name of this customer.
     *
     * @param string $name
     * @return $this
     */
    public function setCustomer($name);

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
}

//    "customer": {
//        "name": "Julio",
//        "email": "email0@entria.com.br",
//        "phone": "119912345670",
//        "taxID": {
//            "taxID": "31928282008",
//            "type": "BR:CPF"
//        },
//        "correlationID": "9134e286-6f71-427a-bf00-241681624586",
//    },
