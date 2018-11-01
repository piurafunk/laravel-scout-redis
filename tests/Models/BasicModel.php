<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 11/1/18
 * Time: 9:31 AM
 */

namespace Test\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BasicModel
 * @package Test\Models
 * @property int $id
 * @property string $word
 */
class BasicModel extends Model
{
    protected $fillable = [
        'word'
    ];
}