<?php

namespace OpenPix\Pix\Api\Data;

interface PixTransactionInterface {
    /**
     * Return the endToEndId.
     *
     * @return string EndToEndId.
     */

    public function getEndToEndId(): string;

    /**
     * Set the endToEndId
     *
     * @param string $endToEndId
     * @return $this
     */
    public function setEndToEndId(string $endToEndId): PixTransactionInterface;
}
