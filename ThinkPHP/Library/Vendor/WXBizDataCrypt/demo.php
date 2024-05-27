<?php

include_once "wxBizDataCrypt.php";


$appid = 'wx9480d55fb4d67322';
$sessionKey = '5jgx2gAwr0ZgbFmBoXAIfg==';

$encryptedData="Bx9Q2iQPkrsnx8Z8BODYpvfz2/7LpaZ1GFPIk/wmvbqypmdTXGnAT7U3CoBN0/PGfHEHJljpAKxQqPsFQRJwVMVXKVa0zjkyd1NbNy0+MORnrSqzG01kOMCi2svakUZez3bRoWIbZ0qjW2ThA5d/QR4QHEnvbT5EHj/XFPXFbKNGiJQt8WwQhIM65lEmqTcOm52Qo/320LJ3TgrLHDjV94ExA2YXChg+QOWtdDiFtbVg9mwuf0SqtuCKdh0koBFYopr+u+s/8Xwou3YT7hmBWPVW47NEQEGhpKPHiF7BUCYpQscQkg/ZPq1yUL/4q9smjwCXvY8vpfD/ibjsRhcXmL2dWUT8VQzymKwrk02O2RkHYdDApIVkA4tcRzj5Qml8F/0Bb+7yniT6f2NnGkRRV4qXdB6SqHJ2LMcHsnAgac0y+xxA/zD3i5rqkLHnoXuogORttu54dGn0azzTnCQxW5KSs6gDRAJi7h13rLoRVXS7Gm0ISFzRt7e9AvkC5GvjJkvnICEgvE5MpbPvx/g3FQ==";

$iv = "93bIO5cbDoWqrEHAOtON9w==";

$pc = new WXBizDataCrypt($appid, $sessionKey);
$errCode = $pc->decryptData($encryptedData, $iv, $data );

if ($errCode == 0) {
    print($data . "\n");
} else {
    print($errCode . "\n");
}
