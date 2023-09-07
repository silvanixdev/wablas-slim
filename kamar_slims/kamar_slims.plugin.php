<?php
/**
 * Plugin Name: Kamar Slims
 * Plugin URI:
 * Description: Circulation Notification using Whatsapp
 * Version: 1.0.0
 * Author: Silvanix
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use SLiMS\DB;
use SLiMS\Plugins;

/**
 * Get plugin instance
 */
$plugin = Plugins::getInstance();

/**
 *  Prepare variable to config
 */
$config = [];
$config['library_name'] = 'Perpustakaan Ideal Serbaguna'; // your library name
$config['footer_text'] = 'Harap simpan resi ini sebagai bukti transaksi.'; // closing message
$config['token'] = 'token'; // token wablas
$config['secret-key'] = '123456789';
$config['service'] = 'slims';

/**
 * Registering hook plugin on circulation_after_successful_transaction
 * In this hook, we will get the data circulation.
 */
$plugin->register("circulation_after_successful_transaction", function($data) use (&$config) {
    if ( (isset($data['loan'])) OR (isset($data['return'])) OR (isset($data['extend'])) ) {
        # Getting member data to get member_phone info.
        $member_data = api::member_load(DB::getInstance('mysqli'), $data['memberID']);
        if (isset($member_data[0]['member_phone'])) {
            # Validasi member_phone.
            $phone = $member_data[0]['member_phone'];
            $phone = str_replace(" ","",$phone);
            $phone = str_replace("-","",$phone);
            $phone = str_replace("+","",$phone);
        
            if(substr($phone, 0, 1)=='0'){
              $phone = '62'.substr($phone, 1);
            }

            # HEADER
            $message = '*'.strtoupper($config['library_name'])."*\n";
            $message .= 'No. Angg : '.$data['memberID']."\n";
            $message .= 'Nama : '.$data['memberName']."\n";
            $message .= 'Jn. Angg : '.$data['memberType']."\n";
            $message .= 'Tanggal : '.$data['date']."\n";
            $messageId = substr(sha1(rand(1, 20).date('UTC')), 0, 16);
            $message .= 'ID : '.$messageId."\n";

            # PEMINJAMAN
            if (isset($data['loan'])) {
                $message .= "=====================\n";
                $message .= "*PEMINJAMAN*\n";
                $message .= "=====================\n";
                foreach($data['loan'] as $lk => $lv) {
                    $message .= '*'.$lv['itemCode']."*\n";
                    $message .= '_'.$lv['title']."_\n";
                    $loanDate = explode('-', $lv['loanDate']);
                    $message .= 'Tanggal pinjam: '.$loanDate[2].'-'.$loanDate[1].'-'.$loanDate[0]."\n";
                    $dueDate = explode('-', $lv['dueDate']);
                    $message .= 'Batas pinjam: '.$dueDate[2].'-'.$dueDate[1].'-'.$dueDate[0]."\n";
                }
            }

            # PENGEMBALIAN
            if (isset($data['return'])) {
                $counter = 0;
                $retmessage = "=====================\n";
                $retmessage .= "*PENGEMBALIAN*\n";
                $retmessage .= "=====================\n";
                foreach($data['return'] as $rk => $rv) {
                    $dup = FALSE;
                    if (isset($data['extend'])) {
                        foreach ($data['extend'] as $_ek => $_ev) {
                            if ($rv['itemCode'] == $_ev['itemCode']) {
                                $dup = TRUE;
                            }
                        }
                    }
                    if (!$dup) {
                        $retmessage .= '*'.$rv['itemCode']."*\n";
                        $retmessage .= '_'.$rv['title']."_\n";
                        $returnDate = explode('-', $rv['returnDate']);
                        $retmessage .= 'Tanggal kembali: '.$returnDate[2].'-'.$returnDate[1].'-'.$returnDate[0]."\n";
                        if ($rv['overdues']) {
                            $retmessage .= 'Denda: '.$rv['overdues']."\n";
                        }
                        $counter++;                  
                    }
                }
                if ($counter > 0) {
                    $message .= $retmessage;
                }
            }

            # PERPANJANGAN
            if (isset($data['extend'])) {
                $message .= "=====================\n";
                $message .= "*PERPANJANGAN*\n";
                $message .= "=====================\n";
                foreach($data['extend'] as $ek => $ev) {
                    $message .= '*'.$ev['itemCode']."*\n";
                    $message .= '_'.$ev['title']."_\n";
                    $loanDate = explode('-', $ev['loanDate']);
                    $message .= 'Tanggal pinjam: '.$loanDate[2].'-'.$loanDate[1].'-'.$loanDate[0]."\n";
                    $dueDate = explode('-', $ev['dueDate']);
                    $message .= 'Batas pinjam: '.$dueDate[2].'-'.$dueDate[1].'-'.$dueDate[0]."\n";
                }
            }

            # FOOTER
            $message .= "\n_____________________\n".$config['footer_text'];
        }

        $client = new Client();
        $headers = [
        'Authorization' => $config['token'],
        'Secret-key' => $config['secret-key'],
        'Service' => $config['service'],
        'Content-Type' => 'application/json'
        ];

        $msg = json_encode($message);

        $body = "{
        \"data\": [
            {
            \"phone\": \"$phone\",
            \"message\": $msg,
            \"secret\": false,
            \"retry\": false,
            \"isGroup\": false
            }
        ]
        }";

        $request = new Request('POST', 'https://texas.wablas.com/api/v2/send', $headers, $body);
        $client->sendAsync($request)->wait();
    }

});
