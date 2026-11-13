<?php

namespace Modrix\ContactForm;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'contact_form_messages';

    protected $fillable = ['name', 'email', 'message', 'page_url', 'ip_address'];
}
