<?php

/**
 * Name: WHMCS eNom Balance Widget
 * Description: This widget provides you with your eNom balance on your WHMCS admin dashboard.
 * Version: 1.1.1
 * Created by Kenny Interactive Hosting
 * Website: https://www.kennyinteractivehosting.com
 */

use WHMCS\Database\Capsule;

add_hook('AdminHomeWidgets', 1, function () {
    return new eNomBalanceWidget();
});

class eNomBalanceWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'Enom Account Balance';
    protected $description = 'Widget provides you with your eNom balance on your admin dashboard. Created by Host Media.';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = true;
    protected $cacheExpiry = 120;

    public function getData()
    {
        // Credentials (replace with your actual values or use dynamic loading below)
        $enomusername = '[USERNAME HERE]';
        $enompassword = '[PASSWORD HERE]';

        // Optional secure loading from database
        // $settings = Capsule::table('tblregistrars')->where('registrar', 'enom')->pluck('value', 'setting');
        // $enomusername = $settings['Username'] ?? '';
        // $enompassword = $settings['Password'] ?? '';

        $enomapiurl = 'https://reseller.enom.com/interface.asp?command=GetBalance&uid=' . $enomusername . '&pw=' . $enompassword . '&responsetype=xml';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $enomapiurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $data = json_decode($json);

        return [
            'enom' => $data,
            'balance' => $data->Balance ?? '0.00',
            'availableBalance' => $data->AvailableBalance ?? '0.00'
        ];
    }

    public function generateOutput($data)
{
    if ($data['enom']->ErrCount > 0) {
        return <<<EOF
<div class="widget-content-padded">
    <div class="alert alert-danger" role="alert">
        <strong>eNom API Error:</strong><br/>
        {$data['enom']->errors->Err1}
    </div>
</div>
EOF;
    }

    return <<<EOF
<style>
    .enom-balance-icon {
        font-size: 36px;
        display: block;
        margin-bottom: 5px;
    }
    .enom-balance-label {
        font-weight: 600;
        font-size: 14px;
        color: #555;
    }
    .enom-balance-amount {
        font-size: 20px;
        font-weight: bold;
        margin-top: 4px;
    }
</style>
<div class="widget-content-padded">
    <div class="row text-center">
        <div class="col-sm-6">
            <span class="enom-balance-icon" style="color: #28a745;">
                <i class="fas fa-dollar-sign fa-lg"></i>
            </span>
            <div class="enom-balance-amount" style="color: #28a745;">
                \${$data['balance']} USD
            </div>
            <div class="enom-balance-label">Current Balance</div>
        </div>
        <div class="col-sm-6">
            <span class="enom-balance-icon" style="color: #e83e8c;">
                <i class="fas fa-dollar-sign fa-lg"></i>
            </span>
            <div class="enom-balance-amount" style="color: #e83e8c;">
                \${$data['availableBalance']} USD
            </div>
            <div class="enom-balance-label">Available Balance</div>
        </div>
    </div>
</div><center><a href="https://www.enom.com/myaccount/RefillAccount.aspx" class="btn btn-default btn-sm" title="Click here to access your Enom account and add funds." target="_blank"><i class="fas fa-credit-card fa-fw"></i> Refill Account</a></center>
EOF;
}
}
