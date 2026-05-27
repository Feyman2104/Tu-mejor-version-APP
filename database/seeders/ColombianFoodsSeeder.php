<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ColombianFoodsSeeder extends Seeder
{
    public function run(): void
    {
        $foods = [
            ['name' => 'Arroz blanco', 'category' => 'cereales', 'kcal' => 130, 'protein_g' => 2.7, 'fat_g' => 0.3, 'carbs_g' => 28, 'fiber_g' => 0.4, 'portion_g' => 150],
            ['name' => 'Arroz integral', 'category' => 'cereales', 'kcal' => 111, 'protein_g' => 2.6, 'fat_g' => 0.9, 'carbs_g' => 23, 'fiber_g' => 1.8, 'portion_g' => 150],
            ['name' => 'Frijoles negros', 'category' => 'leguminosas', 'kcal' => 132, 'protein_g' => 8.9, 'fat_g' => 0.5, 'carbs_g' => 24, 'fiber_g' => 8.7, 'portion_g' => 150],
            ['name' => 'Frijoles rojos', 'category' => 'leguminosas', 'kcal' => 139, 'protein_g' => 8.7, 'fat_g' => 0.5, 'carbs_g' => 25, 'fiber_g' => 7.4, 'portion_g' => 150],
            ['name' => 'Lentejas', 'category' => 'leguminosas', 'kcal' => 116, 'protein_g' => 9.0, 'fat_g' => 0.4, 'carbs_g' => 20, 'fiber_g' => 7.9, 'portion_g' => 150],
            ['name' => 'Garbanzos', 'category' => 'leguminosas', 'kcal' => 164, 'protein_g' => 8.9, 'fat_g' => 2.6, 'carbs_g' => 27, 'fiber_g' => 7.6, 'portion_g' => 150],
            ['name' => 'Papa criolla', 'category' => 'tubérculos', 'kcal' => 85, 'protein_g' => 1.7, 'fat_g' => 0.1, 'carbs_g' => 19, 'fiber_g' => 1.5, 'portion_g' => 150],
            ['name' => 'Papa pastusa', 'category' => 'tubérculos', 'kcal' => 77, 'protein_g' => 2.0, 'fat_g' => 0.1, 'carbs_g' => 17, 'fiber_g' => 2.0, 'portion_g' => 150],
            ['name' => 'Yuca', 'category' => 'tubérculos', 'kcal' => 140, 'protein_g' => 1.3, 'fat_g' => 0.3, 'carbs_g' => 34, 'fiber_g' => 1.1, 'portion_g' => 150],
            ['name' => 'Plátano verde', 'category' => 'tubérculos', 'kcal' => 122, 'protein_g' => 1.3, 'fat_g' => 0.4, 'carbs_g' => 31, 'fiber_g' => 2.3, 'portion_g' => 150],
            ['name' => 'Plátano maduro', 'category' => 'frutas', 'kcal' => 90, 'protein_g' => 1.0, 'fat_g' => 0.1, 'carbs_g' => 23, 'fiber_g' => 1.0, 'portion_g' => 120],
            ['name' => 'Pecho de pollo sin piel', 'category' => 'proteínas', 'kcal' => 110, 'protein_g' => 23, 'fat_g' => 2.5, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 120],
            ['name' => 'Muslo de pollo sin piel', 'category' => 'proteínas', 'kcal' => 120, 'protein_g' => 19, 'fat_g' => 4.5, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 120],
            ['name' => 'Carne molida de res', 'category' => 'proteínas', 'kcal' => 250, 'protein_g' => 26, 'fat_g' => 15, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Bistec de res', 'category' => 'proteínas', 'kcal' => 220, 'protein_g' => 27, 'fat_g' => 12, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Cerdo lomo', 'category' => 'proteínas', 'kcal' => 180, 'protein_g' => 25, 'fat_g' => 8, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Salmón', 'category' => 'proteínas', 'kcal' => 208, 'protein_g' => 20, 'fat_g' => 13, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Tilapia', 'category' => 'proteínas', 'kcal' => 96, 'protein_g' => 20, 'fat_g' => 1.7, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 120],
            ['name' => 'Bagre', 'category' => 'proteínas', 'kcal' => 100, 'protein_g' => 18, 'fat_g' => 2.5, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 120],
            ['name' => 'Huevo entero', 'category' => 'proteínas', 'kcal' => 155, 'protein_g' => 13, 'fat_g' => 11, 'carbs_g' => 1.1, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Clara de huevo', 'category' => 'proteínas', 'kcal' => 52, 'protein_g' => 11, 'fat_g' => 0.2, 'carbs_g' => 0.7, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Atún enlatado en agua', 'category' => 'proteínas', 'kcal' => 100, 'protein_g' => 22, 'fat_g' => 0.8, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Sardina', 'category' => 'proteínas', 'kcal' => 135, 'protein_g' => 18, 'fat_g' => 6.5, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Leche descremada', 'category' => 'lácteos', 'kcal' => 35, 'protein_g' => 3.4, 'fat_g' => 0.1, 'carbs_g' => 5, 'fiber_g' => 0, 'portion_g' => 240],
            ['name' => 'Leche entera', 'category' => 'lácteos', 'kcal' => 61, 'protein_g' => 3.2, 'fat_g' => 3.3, 'carbs_g' => 4.8, 'fiber_g' => 0, 'portion_g' => 240],
            ['name' => 'Yogur natural descremado', 'category' => 'lácteos', 'kcal' => 56, 'protein_g' => 10, 'fat_g' => 0.7, 'carbs_g' => 3.6, 'fiber_g' => 0, 'portion_g' => 170],
            ['name' => 'Queso blanco bajo en grasa', 'category' => 'lácteos', 'kcal' => 80, 'protein_g' => 8, 'fat_g' => 4.5, 'carbs_g' => 1.5, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Queso mozzarella', 'category' => 'lácteos', 'kcal' => 280, 'protein_g' => 28, 'fat_g' => 17, 'carbs_g' => 3.1, 'fiber_g' => 0, 'portion_g' => 100],
            ['name' => 'Arequipe', 'category' => 'lácteos', 'kcal' => 290, 'protein_g' => 7, 'fat_g' => 11, 'carbs_g' => 42, 'fiber_g' => 0, 'portion_g' => 30],
            ['name' => 'Avena en hojuelas', 'category' => 'cereales', 'kcal' => 389, 'protein_g' => 17, 'fat_g' => 7, 'carbs_g' => 66, 'fiber_g' => 11, 'portion_g' => 40],
            ['name' => 'Maíz en granos', 'category' => 'cereales', 'kcal' => 86, 'protein_g' => 3.3, 'fat_g' => 1.4, 'carbs_g' => 17, 'fiber_g' => 2.7, 'portion_g' => 150],
            ['name' => 'Arepa de maíz blanco', 'category' => 'cereales', 'kcal' => 150, 'protein_g' => 3.5, 'fat_g' => 2, 'carbs_g' => 30, 'fiber_g' => 2, 'portion_g' => 100],
            ['name' => 'Pan tajado blanco', 'category' => 'cereales', 'kcal' => 265, 'protein_g' => 9, 'fat_g' => 3.2, 'carbs_g' => 49, 'fiber_g' => 2.7, 'portion_g' => 60],
            ['name' => 'Pan integral', 'category' => 'cereales', 'kcal' => 240, 'protein_g' => 10, 'fat_g' => 4, 'carbs_g' => 45, 'fiber_g' => 6, 'portion_g' => 60],
            ['name' => 'Galletas de agua', 'category' => 'cereales', 'kcal' => 440, 'protein_g' => 9, 'fat_g' => 12, 'carbs_g' => 74, 'fiber_g' => 2.5, 'portion_g' => 40],
            ['name' => 'Mango', 'category' => 'frutas', 'kcal' => 60, 'protein_g' => 0.4, 'fat_g' => 0.3, 'carbs_g' => 15, 'fiber_g' => 1.6, 'portion_g' => 150],
            ['name' => 'Banano', 'category' => 'frutas', 'kcal' => 89, 'protein_g' => 1.1, 'fat_g' => 0.3, 'carbs_g' => 23, 'fiber_g' => 2.6, 'portion_g' => 120],
            ['name' => 'Manzana', 'category' => 'frutas', 'kcal' => 52, 'protein_g' => 0.3, 'fat_g' => 0.2, 'carbs_g' => 14, 'fiber_g' => 2.4, 'portion_g' => 150],
            ['name' => 'Naranja', 'category' => 'frutas', 'kcal' => 47, 'protein_g' => 0.9, 'fat_g' => 0.1, 'carbs_g' => 12, 'fiber_g' => 2.4, 'portion_g' => 150],
            ['name' => 'Papaya', 'category' => 'frutas', 'kcal' => 43, 'protein_g' => 0.5, 'fat_g' => 0.3, 'carbs_g' => 11, 'fiber_g' => 1.7, 'portion_g' => 150],
            ['name' => 'Piña', 'category' => 'frutas', 'kcal' => 50, 'protein_g' => 0.5, 'fat_g' => 0.1, 'carbs_g' => 13, 'fiber_g' => 1.4, 'portion_g' => 150],
            ['name' => 'Fresa', 'category' => 'frutas', 'kcal' => 32, 'protein_g' => 0.7, 'fat_g' => 0.3, 'carbs_g' => 8, 'fiber_g' => 2, 'portion_g' => 150],
            ['name' => 'Aguacate', 'category' => 'grasas', 'kcal' => 160, 'protein_g' => 2, 'fat_g' => 15, 'carbs_g' => 9, 'fiber_g' => 7, 'portion_g' => 100],
            ['name' => 'Aceituna', 'category' => 'grasas', 'kcal' => 115, 'protein_g' => 0.8, 'fat_g' => 11, 'carbs_g' => 3, 'fiber_g' => 2.8, 'portion_g' => 40],
            ['name' => ' Aceite de oliva', 'category' => 'grasas', 'kcal' => 884, 'protein_g' => 0, 'fat_g' => 100, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 10],
            ['name' => 'Mantequilla', 'category' => 'grasas', 'kcal' => 717, 'protein_g' => 0.9, 'fat_g' => 81, 'carbs_g' => 0.1, 'fiber_g' => 0, 'portion_g' => 10],
            ['name' => 'Coco rallado', 'category' => 'grasas', 'kcal' => 660, 'protein_g' => 6.9, 'fat_g' => 65, 'carbs_g' => 24, 'fiber_g' => 16, 'portion_g' => 30],
            ['name' => 'Almendras', 'category' => 'grasas', 'kcal' => 579, 'protein_g' => 21, 'fat_g' => 50, 'carbs_g' => 22, 'fiber_g' => 12, 'portion_g' => 30],
            ['name' => 'Maní', 'category' => 'grasas', 'kcal' => 567, 'protein_g' => 26, 'fat_g' => 49, 'carbs_g' => 16, 'fiber_g' => 8.5, 'portion_g' => 30],
            ['name' => 'Café passado', 'category' => 'bebidas', 'kcal' => 2, 'protein_g' => 0.3, 'fat_g' => 0, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 240],
            ['name' => 'Jugo de naranja natural', 'category' => 'bebidas', 'kcal' => 45, 'protein_g' => 0.7, 'fat_g' => 0.2, 'carbs_g' => 10, 'fiber_g' => 0.2, 'portion_g' => 240],
            ['name' => 'Jugo de mango natural', 'category' => 'bebidas', 'kcal' => 55, 'protein_g' => 0.4, 'fat_g' => 0.1, 'carbs_g' => 13, 'fiber_g' => 0.4, 'portion_g' => 240],
            ['name' => 'Guanábana', 'category' => 'frutas', 'kcal' => 66, 'protein_g' => 1.4, 'fat_g' => 0.3, 'carbs_g' => 17, 'fiber_g' => 3.3, 'portion_g' => 150],
            ['name' => 'Lulo', 'category' => 'frutas', 'kcal' => 21, 'protein_g' => 0.6, 'fat_g' => 0.1, 'carbs_g' => 4.8, 'fiber_g' => 1.1, 'portion_g' => 100],
            ['name' => 'Tomate', 'category' => 'vegetales', 'kcal' => 18, 'protein_g' => 0.9, 'fat_g' => 0.2, 'carbs_g' => 3.9, 'fiber_g' => 1.2, 'portion_g' => 150],
            ['name' => 'Cebolla roja', 'category' => 'vegetales', 'kcal' => 40, 'protein_g' => 1.1, 'fat_g' => 0.1, 'carbs_g' => 9.3, 'fiber_g' => 1.7, 'portion_g' => 100],
            ['name' => 'Cebolla blanca', 'category' => 'vegetales', 'kcal' => 36, 'protein_g' => 1.0, 'fat_g' => 0.1, 'carbs_g' => 8.4, 'fiber_g' => 1.6, 'portion_g' => 100],
            ['name' => 'Cilantro', 'category' => 'vegetales', 'kcal' => 23, 'protein_g' => 2.1, 'fat_g' => 0.5, 'carbs_g' => 3.7, 'fiber_g' => 2.8, 'portion_g' => 20],
            ['name' => 'Ahuyama', 'category' => 'vegetales', 'kcal' => 20, 'protein_g' => 0.7, 'fat_g' => 0.1, 'carbs_g' => 4.8, 'fiber_g' => 1.4, 'portion_g' => 150],
            ['name' => 'Zanahoria', 'category' => 'vegetales', 'kcal' => 41, 'protein_g' => 0.9, 'fat_g' => 0.2, 'carbs_g' => 10, 'fiber_g' => 2.8, 'portion_g' => 150],
            ['name' => 'Lechuga', 'category' => 'vegetales', 'kcal' => 15, 'protein_g' => 1.4, 'fat_g' => 0.2, 'carbs_g' => 2.9, 'fiber_g' => 1.3, 'portion_g' => 80],
            ['name' => 'Espinaca', 'category' => 'vegetales', 'kcal' => 23, 'protein_g' => 2.9, 'fat_g' => 0.4, 'carbs_g' => 3.6, 'fiber_g' => 2.2, 'portion_g' => 100],
            ['name' => 'Brócoli', 'category' => 'vegetales', 'kcal' => 34, 'protein_g' => 2.8, 'fat_g' => 0.4, 'carbs_g' => 7, 'fiber_g' => 2.6, 'portion_g' => 100],
            ['name' => 'Coliflor', 'category' => 'vegetales', 'kcal' => 25, 'protein_g' => 1.9, 'fat_g' => 0.3, 'carbs_g' => 5, 'fiber_g' => 2, 'portion_g' => 100],
            ['name' => 'Habichuela', 'category' => 'vegetales', 'kcal' => 31, 'protein_g' => 1.8, 'fat_g' => 0.1, 'carbs_g' => 7, 'fiber_g' => 3.4, 'portion_g' => 100],
            ['name' => 'Remolacha', 'category' => 'vegetales', 'kcal' => 43, 'protein_g' => 1.6, 'fat_g' => 0.2, 'carbs_g' => 10, 'fiber_g' => 2.8, 'portion_g' => 100],
            ['name' => 'Arracacha', 'category' => 'tubérculos', 'kcal' => 98, 'protein_g' => 2.0, 'fat_g' => 0.4, 'carbs_g' => 22, 'fiber_g' => 2.1, 'portion_g' => 150],
            ['name' => 'Calabaza', 'category' => 'vegetales', 'kcal' => 26, 'protein_g' => 1.0, 'fat_g' => 0.1, 'carbs_g' => 6.5, 'fiber_g' => 0.5, 'portion_g' => 150],
            ['name' => 'Pepino', 'category' => 'vegetales', 'kcal' => 16, 'protein_g' => 0.7, 'fat_g' => 0.1, 'carbs_g' => 3.6, 'fiber_g' => 0.5, 'portion_g' => 150],
            ['name' => 'Palta', 'category' => 'grasas', 'kcal' => 160, 'protein_g' => 2, 'fat_g' => 15, 'carbs_g' => 9, 'fiber_g' => 7, 'portion_g' => 100],
            ['name' => 'Sopa de lentejas', 'category' => 'sopas', 'kcal' => 130, 'protein_g' => 8, 'fat_g' => 2, 'carbs_g' => 22, 'fiber_g' => 7, 'portion_g' => 250],
            ['name' => 'Sopa de pollo', 'category' => 'sopas', 'kcal' => 75, 'protein_g' => 8, 'fat_g' => 3, 'carbs_g' => 4, 'fiber_g' => 0.5, 'portion_g' => 250],
            ['name' => 'Mondongo', 'category' => 'sopas', 'kcal' => 110, 'protein_g' => 9, 'fat_g' => 4, 'carbs_g' => 10, 'fiber_g' => 1.5, 'portion_g' => 250],
            ['name' => 'Changua', 'category' => 'sopas', 'kcal' => 90, 'protein_g' => 7, 'fat_g' => 4, 'carbs_g' => 5, 'fiber_g' => 0.3, 'portion_g' => 250],
            ['name' => 'Ajiaco', 'category' => 'sopas', 'kcal' => 145, 'protein_g' => 10, 'fat_g' => 5, 'carbs_g' => 18, 'fiber_g' => 3, 'portion_g' => 300],
            ['name' => 'Patacón', 'category' => 'snacks', 'kcal' => 180, 'protein_g' => 1, 'fat_g' => 10, 'carbs_g' => 22, 'fiber_g' => 2, 'portion_g' => 100],
            ['name' => 'Chicharrón', 'category' => 'snacks', 'kcal' => 330, 'protein_g' => 15, 'fat_g' => 28, 'carbs_g' => 0, 'fiber_g' => 0, 'portion_g' => 50],
            ['name' => 'Croquetas de pollo', 'category' => 'snacks', 'kcal' => 290, 'protein_g' => 12, 'fat_g' => 18, 'carbs_g' => 22, 'fiber_g' => 1, 'portion_g' => 80],
            ['name' => 'Empanada de carne', 'category' => 'snacks', 'kcal' => 260, 'protein_g' => 8, 'fat_g' => 14, 'carbs_g' => 28, 'fiber_g' => 2, 'portion_g' => 100],
            ['name' => 'Deditos de queso', 'category' => 'snacks', 'kcal' => 280, 'protein_g' => 9, 'fat_g' => 16, 'carbs_g' => 28, 'fiber_g' => 1, 'portion_g' => 80],
            ['name' => 'Buñuelo', 'category' => 'snacks', 'kcal' => 250, 'protein_g' => 6, 'fat_g' => 14, 'carbs_g' => 28, 'fiber_g' => 0.8, 'portion_g' => 80],
            ['name' => 'Panquema', 'category' => 'snacks', 'kcal' => 280, 'protein_g' => 5, 'fat_g' => 16, 'carbs_g' => 32, 'fiber_g' => 0.5, 'portion_g' => 80],
            ['name' => 'Te rojo', 'category' => 'bebidas', 'kcal' => 2, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 0.5, 'fiber_g' => 0, 'portion_g' => 240],
            ['name' => 'Agua de panca', 'category' => 'bebidas', 'kcal' => 45, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 11, 'fiber_g' => 0, 'portion_g' => 240],
        ];

        foreach ($foods as $food) {
            Food::updateOrCreate(
                ['slug' => Str::slug($food['name'])],
                $food
            );
        }
    }
}