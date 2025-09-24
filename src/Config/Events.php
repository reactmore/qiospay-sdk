<?php

namespace Reactmore\QiosPay\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("qiospay");
});
