<?php

namespace Frontend\Services;

use Common\Services\Sql;
use Frontend\Module;

/**
 * Class Finance
 *
 * @package Frontend\Services
 */
class Finance extends AbstractService
{
    /**
     * Return factor's fiscal data
     *
     * @param $id
     * @return mixed
     */
    public function getFactorFinanceData($id)
    {
        $sql
            = "
            SELECT
                fm.telephone,
                fm.timezone,
                ff.paypal_email,
                ff.tax_id,
                fr.street,
                fr.interior_number,
                fr.exterior_number,
                fr.suburb suburb,
                fr.colony,
                fr.state,
                fr.city,
                fr.postcode,
                fr.country
            FROM factor_meta fm
                LEFT JOIN factor_fiscal ff ON ff.id_factor = fm.id_factor
                left JOIN factor_region fr ON fr.id_factor = fm.id_factor
            WHERE
                fm.id_factor = ".$id;
        $data = Sql::find($sql);

        return $data[0];
    }

    /**
     * Return transactions
     *
     * @param $id
     * @return array
     */
    public function getFactorTransactions($id)
    {
        $sql
            = "SELECT
              t.amount,
              t.description,
              t.created_at,
              t.authorization,
              fm.timezone
            FROM transaction t
              INNER JOIN factor_transaction ft ON t.id = ft.id_transaction
              INNER JOIN factor_meta fm ON fm.id_factor = ft.id_factor
            WHERE ft.id_factor = ".$id."
            AND t.status IN('approved', 'complete')
            ORDER BY t.created_at DESC";
        $transactions = Sql::find($sql);
        $service = Module::getService('Time');
        foreach ($transactions as $i => $txn) {
            $transactions[$i]['created_at'] = $service->utcToTimezone($txn['created_at'], $txn['timezone'], 'd/m/Y h:i A');
        }

        return $transactions;
    }

}