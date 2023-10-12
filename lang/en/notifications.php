<?php

return [
    'verify_email' => [
        'title' => 'Verify Email',

        'mail' => [
            'greeting' => 'Hi, :username',
            'subject'  => 'Verify Email Address',
            'content'  => 'Please click the button below to verify your email address.',
            'action'   => 'Verify',
        ],
    ],

    'email_token' => [
        'title' => 'Email Token',

        'mail' => [
            'greeting' => 'Hi, :username',
            'subject'  => 'OTP Request',
            'content'  => '<b>:code</b> is your verification code, it expires in <b>:minutes</b> minutes.',
        ],
    ],

    'product_purchase' => [
        'title' => 'Product purchase',

        'mail' => [
            'greeting' => 'Hi, :username',
            'subject'  => 'Thank you for your purchase!',
            'content'  => 'We are happy to inform you that your <b>:items_count</b> product purchases of <b>:total</b> in total is now available in your account.',
        ],

        'sms' => [
            'content' => 'Your :items_count product purchases of :total is now available in your account.',
        ],

        'database' => [
            'content' => 'Your :items_count product purchases of :total is now available in your account.',
        ],
    ],

    'user_activity' => [
        'title' => 'Account changes',

        'mail' => [
            'greeting' => 'Hi, :username',
            'subject'  => 'Change detected in your account',
            'content'  => 'We detected the activity: <b>:action</b> on your account <br/> <br/> IP address: <b>:ip</b>  <br/> Browser: <b>:agent</b> <br/> Country: <b>:country</b>  <br/> <br/> If this was not you, please contact our help center as soon as possible.',
        ],

        'sms' => [
            'content' => 'We detected the following activity on your account: :action',
        ],

        'database' => [
            'content' => 'We detected the following activity on your account: :action',
        ],
    ],
];
