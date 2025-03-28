<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class CtlProductos extends Model
{
    //
    use HasFactory, Notifiable, HasRoles;
    protected $table = 'ctl_productos';
    protected $fillable = [
        'nombre',
        'precio',
        'imagen',
        'categoria_id'
    ];


    public function categoria(){
        return $this->belongsTo(CtlCategoria::class,'categoria_id','id');
    }
    public function inventario(){
        return $this->hasMany(CtlInventerio::class,'producto_id','id');
    }
    public function detallePedido(){
        return $this->hasMany(MntDetallePedidos::class,'producto_id','id');
    }
}
