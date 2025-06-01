<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Fee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Testes unitários para o Model Fee.
 *
 * Verifica os casts de atributos, o relacionamento com Event,
 * e a capacidade de atribuição em massa.
 */
#[CoversClass(Fee::class)]
#[Group('model')]
#[Group('fee-model')]
class FeeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se os atributos são corretamente convertidos (casted).
     * Especificamente, `price` para float (decimal com 2 casas) e
     * `is_discount_for_main_event_participant` para boolean.
     */
    public function test_attributes_are_casted_correctly(): void
    {
        $fee = Fee::factory()->create([
            'price' => '123.45',
            'is_discount_for_main_event_participant' => 1, // Test with integer
        ]);

        $this->assertIsFloat($fee->price); // Eloquent casts 'decimal:2' to float
        $this->assertEquals(123.45, $fee->price);
        $this->assertIsBool($fee->is_discount_for_main_event_participant);
        $this->assertTrue($fee->is_discount_for_main_event_participant);

        // Testa com 0 para booleano
        $feeWithZeroDiscount = Fee::factory()->create([
            'is_discount_for_main_event_participant' => 0,
        ]);
        $this->assertIsBool($feeWithZeroDiscount->is_discount_for_main_event_participant);
        $this->assertFalse($feeWithZeroDiscount->is_discount_for_main_event_participant);

        // Testa com string 'false' para booleano (geralmente o Laravel não faz cast automático assim, mas a factory pode)
        // A factory usa $this->faker->boolean(), que retorna true/false.
        // Testar a persistência de booleanos é mais relevante em Feature tests ou ao salvar explicitamente.
        // Aqui, focamos no cast ao ler.
    }

    /**
     * Testa o relacionamento belongsTo com o Model Event.
     * Garante que uma Fee está corretamente associada a um Event
     * através da chave estrangeira `event_code` e da chave proprietária `code`.
     */
    public function test_fee_belongs_to_event(): void
    {
        // Cria um evento com um código específico
        $event = Event::factory()->create(['code' => 'EVENT2025']);

        // Cria uma taxa associada a esse evento usando o código
        $fee = Fee::factory()->create(['event_code' => $event->code]);

        $this->assertInstanceOf(Event::class, $fee->event);
        $this->assertEquals($event->id, $fee->event->id); // Compara IDs para garantir que é o mesmo evento
        $this->assertEquals('EVENT2025', $fee->event->code); // Verifica se o código do evento é o esperado
    }

    /**
     * Testa se todos os campos definidos em $fillable podem ser atribuídos em massa.
     * Cria uma instância de Fee usando o método `create` com todos os atributos
     * fillable e verifica se os dados foram persistidos corretamente.
     */
    public function test_all_fillable_attributes_can_be_mass_assigned(): void
    {
        $event = Event::factory()->create(); // Evento necessário para a FK

        $fillableAttributes = (new Fee)->getFillable();
        $testData = [];

        // Gera dados de teste com base nos atributos fillable
        // e nos tipos esperados para cada campo.
        foreach ($fillableAttributes as $attribute) {
            match ($attribute) {
                'event_code' => $testData[$attribute] = $event->code,
                'participant_category' => $testData[$attribute] = 'student_national',
                'type' => $testData[$attribute] = 'online_live',
                'period' => $testData[$attribute] = 'late_registration',
                'price' => $testData[$attribute] = 199.99, // Valor decimal
                'is_discount_for_main_event_participant' => $testData[$attribute] = true, // Valor booleano
                default => $testData[$attribute] = 'test_value_for_'.$attribute, // Fallback genérico
            };
        }

        $fee = Fee::create($testData);

        // Verifica se o registro foi criado no banco com um dos identificadores chave
        $this->assertDatabaseHas('fees', ['event_code' => $event->code, 'participant_category' => 'student_national']);

        // Verifica cada atributo fillable no modelo recuperado
        foreach ($fillableAttributes as $attribute) {
            if (is_bool($testData[$attribute])) {
                // Para booleanos, o valor no banco pode ser 0/1, mas o modelo faz o cast para bool.
                $this->assertEquals((bool) $testData[$attribute], (bool) $fee->{$attribute});
            } elseif ($attribute === 'price') {
                // Para decimais, compara como float após o cast.
                $this->assertEquals((float) $testData[$attribute], (float) $fee->{$attribute});
            } else {
                $this->assertEquals($testData[$attribute], $fee->{$attribute});
            }
        }
    }
}
