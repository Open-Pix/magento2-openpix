<?php

namespace OpenPix\Pix\Api\Data\PixTransaction;

interface PixTransactionInterface {

    /**
     * @var string
     */
    const END_TO_END_ID = 'end_to_end_id';

        /**
     * Return the endToEndId.
     *
     * @return string EndToEndId.
     */
    public function getEndToEndId();

    /**
     * Set the endToEndId
     *
     * @param string $endToEndId
     * @return $this
     */
    public function setEndToEndId($endToEndId);
}
