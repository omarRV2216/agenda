<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;



class Empleado extends Model
{
    use HasFactory;
    public $id;
    public $role_id;
    public $name;
    public $username;
    public $password;
    public $phone;
    public $gender;
    public $active;

    public function Create_user(){
        $sql= "INSERT INTO users 
					(id, 
					role_id, 
					name, 
					username, 
					password, 
					phone, 
					gender, 
					active
                    )

					SELECT RIGHT(CONCAT('00000' , IFNULL(MAX(id),0)+1),6), 
					:role_id, 
					:name, 
					:username, 
					:password, 
					:phone, 
					:gender, 
					:active
					FROM users";
        
        try {
                        
            // Ejecutar el insert
            DB::insert($sql, [
                'role_id'         => $this->role_id,
                'name'           => $this->name,
                'username'           => $this->username,
                'password' => Hash::make($this->password),
                'phone'            => $this->phone ?? '',
                'gender'      => $this->gender ?? '',
                'active'      => $this->active ?? '',
            ]);
            
            return $sql;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}
