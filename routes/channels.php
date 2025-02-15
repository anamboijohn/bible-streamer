<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('bible-verses', function () {
    return true;
});
