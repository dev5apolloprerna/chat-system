<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use App\Models\EmailTemplate;
use App\Models\AppMaster;
use App\Models\Reference;
use App\Models\RatingDetail;
use Illuminate\Support\Facades\Mail;


function FolderPath($folderName)
{
    if ($_SERVER['SERVER_NAME'] == "127.0.0.1") {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . $folderName;
    } else {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . $folderName;
    }
}
