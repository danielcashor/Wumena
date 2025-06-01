<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Productos>
 */
class ProductosFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {

        $categoria = ["peluche", "figura"];
        $precio = [9.99, 10.99, 15.99, 19.99, 14.99];

        return [
            'Nombre' => $this->faker->name(),  
            'Precio' => $this->faker->randomElement($precio),
            'Categoria' => $this->faker->randomElement($categoria),
        ];
    }
}
