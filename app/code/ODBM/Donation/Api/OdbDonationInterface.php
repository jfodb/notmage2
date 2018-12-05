<?php
namespace ODBM\Donation\Api;

interface OdbDonationInterface
{
    /**
     * Returns an array of possible motivation codes in the random pool.
     * If a motivaiton code is given, then it will return data for a specific motivaiton code
     *
     * @api
     * @param string $motivation Motivation code supplied.
     * @return mixed List of products in random pool.
     */
    public function get_cause_pool( $motivation = '' );

    /**
     * Returns an array of possible motivation codes in the random pool.
     * If a motivaiton code is given, then it will return data for a specific motivaiton code
     *
     * @api
     * @param mixed $motivation Motivation code supplied.
     * @return array {
     *              float raised
     *              float goal
     *              int total order
     *              float offlineGiving
     *         }
     */
    public function get_fundraiser_data( $motivation = '' );
}