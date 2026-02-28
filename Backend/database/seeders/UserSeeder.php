<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
	    $path = storage_path('app/private/users.csv');

	    if (!File::exists($path)) {
		    $this->command->error("El archivo users.csv no existe en storage/app/private/");
		    return;
	    }

	    $file = fopen($path, 'r');
	    $header = fgetcsv($file, 1000, ',');

	    $usersCreated = 0;

	    while (($row = fgetcsv($file, 1000, ',')) !== false) {
		    $data = array_combine($header, $row);

		    $user = User::create([
			    'id' => Str::uuid(),
			    'name' => $data['name'],
			    'email' => $data['email'],
			    'password' => Hash::make($data['password']),
			    'role' => $data['role'],
			    'total_kudos' => (int) $data['total_kudos'],
			    'creations_accepted' => (int) $data['creations_accepted'],
			    'email_verified_at' => now(),
			    'created_at' => now(),
			    'updated_at' => now(),
		    ]);

		    // Crear perfil asociado al usuario
		    Profile::create([
			    'id' => Str::uuid(),
			    'user_id' => $user->id,
			    'avatar' => null,
			    'biography' => "Usuario apasionado de Kudos. Me encanta descubrir y votar por los mejores productos.",
			    'social_links' => json_encode([
				    'twitter' => null,
				    'instagram' => null,
				    'linkedin' => null,
			    ]),
			    'city' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao'][rand(0, 4)],
			    'birthdate' => now()->subYears(rand(20, 50))->format('Y-m-d'),
			    'created_at' => now(),
			    'updated_at' => now(),
		    ]);

		    $usersCreated++;
	    }

	    fclose($file);

	    $this->command->info("✅ {$usersCreated} usuarios y perfiles importados correctamente.");
    }
}
