<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxRates = [
            // Major economies
            [
                'country' => 'United States',
                'country_code' => 'US',
                'tax_name' => 'Sales Tax (varies by state)',
                'tax_type' => 'Sales Tax',
                'rate' => 0
            ],

            [
                'country' => 'United Kingdom',
                'country_code' => 'GB',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 20
            ],

            [
                'country' => 'Germany',
                'country_code' => 'DE',
                'tax_name' => 'Mehrwertsteuer',
                'tax_type' => 'VAT',
                'rate' => 19
            ],

            [
                'country' => 'France',
                'country_code' => 'FR',
                'tax_name' => 'Taxe sur la valeur ajoutée',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Canada',
                'country_code' => 'CA',
                'tax_name' => 'Goods and Services Tax',
                'tax_type' => 'GST',
                'rate' => 5
            ],
            [
                'country' => 'Australia',
                'country_code' => 'AU',
                'tax_name' => 'Goods and Services Tax',
                'tax_type' => 'GST',
                'rate' => 10
            ],
            [
                'country' => 'Japan',
                'country_code' => 'JP',
                'tax_name' => 'Consumption Tax',
                'tax_type' => 'Consumption Tax',
                'rate' => 10
            ],
            [
                'country' => 'India',
                'country_code' => 'IN',
                'tax_name' => 'Goods and Services Tax',
                'tax_type' => 'GST',
                'rate' => 18
            ],
            [
                'country' => 'China',
                'country_code' => 'CN',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 13
            ],
            [
                'country' => 'Brazil',
                'country_code' => 'BR',
                'tax_name' => 'ICMS',
                'tax_type' => 'ICMS',
                'rate' => 17
            ],

            // European Union
            ['country' => 'Italy', 'country_code' => 'IT', 'tax_name' => 'Imposta sul Valore Aggiunto', 'tax_type' => 'VAT', 'rate' => 22],
            ['country' => 'Spain', 'country_code' => 'ES', 'tax_name' => 'Impuesto sobre el Valor Añadido', 'tax_type' => 'VAT', 'rate' => 21],
            ['country' => 'Netherlands', 'country_code' => 'NL', 'tax_name' => 'Belasting over de Toegevoegde Waarde', 'tax_type' => 'VAT', 'rate' => 21],
            ['country' => 'Belgium', 'country_code' => 'BE', 'tax_name' => 'Belasting over de Toegevoegde Waarde', 'tax_type' => 'VAT', 'rate' => 21],
            [
                'country' => 'Austria',
                'country_code' => 'AT',
                'tax_name' => 'Umsatzsteuer',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Sweden',
                'country_code' => 'SE',
                'tax_name' => 'Mervärdesskatt',
                'tax_type' => 'VAT',
                'rate' => 25
            ],
            [
                'country' => 'Denmark',
                'country_code' => 'DK',
                'tax_name' => 'Moms',
                'tax_type' => 'VAT',
                'rate' => 25
            ],
            [
                'country' => 'Finland',
                'country_code' => 'FI',
                'tax_name' => 'Arvonlisävero',
                'tax_type' => 'VAT',
                'rate' => 24
            ],
            [
                'country' => 'Norway',
                'country_code' => 'NO',
                'tax_name' => 'Merverdiavgift',
                'tax_type' => 'VAT',
                'rate' => 25
            ],
            [
                'country' => 'Switzerland',
                'country_code' => 'CH',
                'tax_name' => 'Mehrwertsteuer',
                'tax_type' => 'VAT',
                'rate' => 7.7
            ],
            [
                'country' => 'Poland',
                'country_code' => 'PL',
                'tax_name' => 'Podatek od towarów i usług',
                'tax_type' => 'VAT',
                'rate' => 23
            ],
            [
                'country' => 'Czech Republic',
                'country_code' => 'CZ',
                'tax_name' => 'Daň z přidané hodnoty',
                'tax_type' => 'VAT',
                'rate' => 21
            ],
            [
                'country' => 'Hungary',
                'country_code' => 'HU',
                'tax_name' => 'Általános forgalmi adó',
                'tax_type' => 'VAT',
                'rate' => 27
            ],
            [
                'country' => 'Romania',
                'country_code' => 'RO',
                'tax_name' => 'Taxa pe valoarea adăugată',
                'tax_type' => 'VAT',
                'rate' => 19
            ],
            [
                'country' => 'Greece',
                'country_code' => 'GR',
                'tax_name' => 'Φόρος Προστιθέμενης Αξίας',
                'tax_type' => 'VAT',
                'rate' => 24
            ],
            [
                'country' => 'Portugal',
                'country_code' => 'PT',
                'tax_name' => 'Imposto sobre o Valor Acrescentado',
                'tax_type' => 'VAT',
                'rate' => 23
            ],
            [
                'country' => 'Ireland',
                'country_code' => 'IE',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 23
            ],
            [
                'country' => 'Croatia',
                'country_code' => 'HR',
                'tax_name' => 'Porez na dodanu vrijednost',
                'tax_type' => 'VAT',
                'rate' => 25
            ],
            [
                'country' => 'Slovenia',
                'country_code' => 'SI',
                'tax_name' => 'Davek na dodano vrednost',
                'tax_type' => 'VAT',
                'rate' => 22
            ],
            [
                'country' => 'Slovakia',
                'country_code' => 'SK',
                'tax_name' => 'Daň z pridanej hodnoty',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Lithuania',
                'country_code' => 'LT',
                'tax_name' => 'Pridėtinės vertės mokestis',
                'tax_type' => 'VAT',
                'rate' => 21
            ],
            [
                'country' => 'Latvia',
                'country_code' => 'LV',
                'tax_name' => 'Pievienotās vērtības nodoklis',
                'tax_type' => 'VAT',
                'rate' => 21
            ],
            [
                'country' => 'Estonia',
                'country_code' => 'EE',
                'tax_name' => 'Käibemaks',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Bulgaria',
                'country_code' => 'BG',
                'tax_name' => 'Данък върху добавената стойност',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Cyprus',
                'country_code' => 'CY',
                'tax_name' => 'Φόρος Προστιθέμενης Αξίας',
                'tax_type' => 'VAT',
                'rate' => 19
            ],
            [
                'country' => 'Malta',
                'country_code' => 'MT',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 18
            ],
            [
                'country' => 'Luxembourg',
                'country_code' => 'LU',
                'tax_name' => 'Taxe sur la valeur ajoutée',
                'tax_type' => 'VAT',
                'rate' => 17
            ],

            // Asia-Pacific
            [
                'country' => 'South Korea',
                'country_code' => 'KR',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 10
            ],
            [
                'country' => 'Singapore',
                'country_code' => 'SG',
                'tax_name' => 'Goods and Services Tax',
                'tax_type' => 'GST',
                'rate' => 8
            ],
            [
                'country' => 'Hong Kong',
                'country_code' => 'HK',
                'tax_name' => 'No Sales Tax',
                'tax_type' => 'None',
                'rate' => 0
            ],
            [
                'country' => 'Taiwan',
                'country_code' => 'TW',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 5
            ],
            [
                'country' => 'Thailand',
                'country_code' => 'TH',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 7
            ],
            [
                'country' => 'Malaysia',
                'country_code' => 'MY',
                'tax_name' => 'Sales and Service Tax',
                'tax_type' => 'SST',
                'rate' => 10
            ],
            [
                'country' => 'Indonesia',
                'country_code' => 'ID',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 11
            ],
            [
                'country' => 'Philippines',
                'country_code' => 'PH',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 12
            ],
            [
                'country' => 'Vietnam',
                'country_code' => 'VN',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 10
            ],
            [
                'country' => 'New Zealand',
                'country_code' => 'NZ',
                'tax_name' => 'Goods and Services Tax',
                'tax_type' => 'GST',
                'rate' => 15
            ],

            // Middle East
            [
                'country' => 'United Arab Emirates',
                'country_code' => 'AE',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 5
            ],
            [
                'country' => 'Saudi Arabia',
                'country_code' => 'SA',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 15
            ],
            [
                'country' => 'Israel',
                'country_code' => 'IL',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 17
            ],
            [
                'country' => 'Turkey',
                'country_code' => 'TR',
                'tax_name' => 'Katma Değer Vergisi',
                'tax_type' => 'VAT',
                'rate' => 18
            ],
            [
                'country' => 'Egypt',
                'country_code' => 'EG',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 14
            ],

            // Africa
            [
                'country' => 'South Africa',
                'country_code' => 'ZA',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 15
            ],
            [
                'country' => 'Nigeria',
                'country_code' => 'NG',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 7.5
            ],
            [
                'country' => 'Kenya',
                'country_code' => 'KE',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 16
            ],
            [
                'country' => 'Ghana',
                'country_code' => 'GH',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 12.5
            ],
            [
                'country' => 'Morocco',
                'country_code' => 'MA',
                'tax_name' => 'Taxe sur la valeur ajoutée',
                'tax_type' => 'VAT',
                'rate' => 20
            ],

            // Latin America
            [
                'country' => 'Mexico',
                'country_code' => 'MX',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 16
            ],
            [
                'country' => 'Argentina',
                'country_code' => 'AR',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 21
            ],
            [
                'country' => 'Chile',
                'country_code' => 'CL',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 19
            ],
            [
                'country' => 'Colombia',
                'country_code' => 'CO',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 19
            ],
            [
                'country' => 'Peru',
                'country_code' => 'PE',
                'tax_name' => 'Impuesto General a las Ventas',
                'tax_type' => 'VAT',
                'rate' => 18
            ],
            [
                'country' => 'Uruguay',
                'country_code' => 'UY',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 22
            ],
            [
                'country' => 'Ecuador',
                'country_code' => 'EC',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 12
            ],
            [
                'country' => 'Venezuela',
                'country_code' => 'VE',
                'tax_name' => 'Impuesto al Valor Agregado',
                'tax_type' => 'VAT',
                'rate' => 16
            ],

            // Eastern Europe & Russia
            [
                'country' => 'Russia',
                'country_code' => 'RU',
                'tax_name' => 'Налог на добавленную стоимость',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Ukraine',
                'country_code' => 'UA',
                'tax_name' => 'Податок на додану вартість',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Belarus',
                'country_code' => 'BY',
                'tax_name' => 'Падатак на дададзеную вартасць',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Serbia',
                'country_code' => 'RS',
                'tax_name' => 'Порез на додату вредност',
                'tax_type' => 'VAT',
                'rate' => 20
            ],

            // Additional Countries
            [
                'country' => 'Iceland',
                'country_code' => 'IS',
                'tax_name' => 'Virðisaukaskattur',
                'tax_type' => 'VAT',
                'rate' => 24
            ],
            [
                'country' => 'Liechtenstein',
                'country_code' => 'LI',
                'tax_name' => 'Mehrwertsteuer',
                'tax_type' => 'VAT',
                'rate' => 7.7
            ],
            [
                'country' => 'Monaco',
                'country_code' => 'MC',
                'tax_name' => 'Taxe sur la valeur ajoutée',
                'tax_type' => 'VAT',
                'rate' => 20
            ],
            [
                'country' => 'Andorra',
                'country_code' => 'AD',
                'tax_name' => 'Impost General Indirecte',
                'tax_type' => 'VAT',
                'rate' => 4.5
            ],
            [
                'country' => 'San Marino',
                'country_code' => 'SM',
                'tax_name' => 'Imposta Generale sui Consumi',
                'tax_type' => 'VAT',
                'rate' => 17
            ],

            // Additional Asian Countries
            [
                'country' => 'Bangladesh',
                'country_code' => 'BD',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 15
            ],
            [
                'country' => 'Pakistan',
                'country_code' => 'PK',
                'tax_name' => 'Sales Tax',
                'tax_type' => 'Sales Tax',
                'rate' => 17
            ],
            [
                'country' => 'Sri Lanka',
                'country_code' => 'LK',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 18
            ],
            [
                'country' => 'Nepal',
                'country_code' => 'NP',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 13
            ],
            [
                'country' => 'Myanmar',
                'country_code' => 'MM',
                'tax_name' => 'Commercial Tax',
                'tax_type' => 'Commercial Tax',
                'rate' => 5
            ],
            [
                'country' => 'Cambodia',
                'country_code' => 'KH',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 10
            ],
            [
                'country' => 'Laos',
                'country_code' => 'LA',
                'tax_name' => 'Value Added Tax',
                'tax_type' => 'VAT',
                'rate' => 10
            ],
        ];

        $taxRates = array_map(function ($rate) {
            return array_merge($rate, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $taxRates);

        TaxRate::insert($taxRates);
    }
}
