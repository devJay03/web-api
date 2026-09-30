<?php

namespace App\Core;

abstract class Config
{
  const type = 'mysql';
  const host = 'localhost';
  const name = 'api_db';
  const user = 'root';
  const pass = '';
  const charset = 'utf8';
}