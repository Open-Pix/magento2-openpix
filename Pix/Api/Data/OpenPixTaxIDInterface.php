<?php

namespace OpenPix\Pix\Api\Data;

interface OpenPixTaxIDInterface {
    /**
     * Return the taxID.
     *
     * @return string TaxID.
     */
    public function getTaxID();

    /**
     * Set the taxID
     *
     * @param string $taxID
     * @return $this
     */
    public function setTaxID($taxID);

    /**
     * Return the type.
     *
     * @return string Type.
     */
    public function getType();

    /**
     * Set the type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type);
}
