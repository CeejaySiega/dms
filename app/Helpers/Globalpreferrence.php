<?php

namespace App\Helpers;
class Globalpreferrence
{ 
public static function Campuses(){
      $campuses = [
          'SG' => ['ID' => 1, "Campus" => "Main Campus", "Icon" => "fa-cogs","Color" => "primary"],
          'MCC' => ['ID' => 2, "Campus" => "Maasin City Campus", "Icon" => "fa-users","Color" => "danger"],
          'TO' => ['ID' => 3, "Campus" => "Tomas Oppus Campus", "Icon" => "fa-graduation-cap","Color" => "info"],
          'BN' => ['ID' => 4, "Campus" => "Bontoc Campus", "Icon" => "fa-ship","Color" => "primary"],
          'SJ' => ['ID' => 5, "Campus" => "San Juan Campus", "Icon" => "fa-briefcase","Color" => "danger"],
          'HN' => ['ID' => 6, "Campus" => "Hinunangan Campus", "Icon" => "fa-leaf","Color" => "success"],
          'OJT' => ['ID' => 7, "Campus" => "Intern", "Icon" => "fa-city","Color" => "warning"],
      ];
      return $campuses;
  }
}