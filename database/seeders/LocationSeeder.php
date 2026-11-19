<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['AF', 'Afghanistan', '+93'], ['AL', 'Albania', '+355'], ['DZ', 'Algeria', '+213'],
            ['AD', 'Andorra', '+376'], ['AO', 'Angola', '+244'], ['AG', 'Antigua and Barbuda', '+1-268'],
            ['AR', 'Argentina', '+54'], ['AM', 'Armenia', '+374'], ['AU', 'Australia', '+61'],
            ['AT', 'Austria', '+43'], ['AZ', 'Azerbaijan', '+994'], ['BS', 'Bahamas', '+1-242'],
            ['BH', 'Bahrain', '+973'], ['BD', 'Bangladesh', '+880'], ['BB', 'Barbados', '+1-246'],
            ['BY', 'Belarus', '+375'], ['BE', 'Belgium', '+32'], ['BZ', 'Belize', '+501'],
            ['BJ', 'Benin', '+229'], ['BT', 'Bhutan', '+975'], ['BO', 'Bolivia', '+591'],
            ['BA', 'Bosnia and Herzegovina', '+387'], ['BW', 'Botswana', '+267'], ['BR', 'Brazil', '+55'],
            ['BN', 'Brunei', '+673'], ['BG', 'Bulgaria', '+359'], ['BF', 'Burkina Faso', '+226'],
            ['BI', 'Burundi', '+257'], ['CV', 'Cabo Verde', '+238'], ['KH', 'Cambodia', '+855'],
            ['CM', 'Cameroon', '+237'], ['CA', 'Canada', '+1'], ['CF', 'Central African Republic', '+236'],
            ['TD', 'Chad', '+235'], ['CL', 'Chile', '+56'], ['CN', 'China', '+86'],
            ['CO', 'Colombia', '+57'], ['KM', 'Comoros', '+269'], ['CG', 'Congo', '+242'],
            ['CD', 'DR Congo', '+243'], ['CR', 'Costa Rica', '+506'], ['CI', "Côte d'Ivoire", '+225'],
            ['HR', 'Croatia', '+385'], ['CU', 'Cuba', '+53'], ['CY', 'Cyprus', '+357'],
            ['CZ', 'Czechia', '+420'], ['DK', 'Denmark', '+45'], ['DJ', 'Djibouti', '+253'],
            ['DM', 'Dominica', '+1-767'], ['DO', 'Dominican Republic', '+1-809'], ['EC', 'Ecuador', '+593'],
            ['EG', 'Egypt', '+20'], ['SV', 'El Salvador', '+503'], ['GQ', 'Equatorial Guinea', '+240'],
            ['ER', 'Eritrea', '+291'], ['EE', 'Estonia', '+372'], ['SZ', 'Eswatini', '+268'],
            ['ET', 'Ethiopia', '+251'], ['FJ', 'Fiji', '+679'], ['FI', 'Finland', '+358'],
            ['FR', 'France', '+33'], ['GA', 'Gabon', '+241'], ['GM', 'Gambia', '+220'],
            ['GE', 'Georgia', '+995'], ['DE', 'Germany', '+49'], ['GH', 'Ghana', '+233'],
            ['GR', 'Greece', '+30'], ['GD', 'Grenada', '+1-473'], ['GT', 'Guatemala', '+502'],
            ['GN', 'Guinea', '+224'], ['GW', 'Guinea-Bissau', '+245'], ['GY', 'Guyana', '+592'],
            ['HT', 'Haiti', '+509'], ['HN', 'Honduras', '+504'], ['HU', 'Hungary', '+36'],
            ['IS', 'Iceland', '+354'], ['IN', 'India', '+91'], ['ID', 'Indonesia', '+62'],
            ['IR', 'Iran', '+98'], ['IQ', 'Iraq', '+964'], ['IE', 'Ireland', '+353'],
            ['IL', 'Israel', '+972'], ['IT', 'Italy', '+39'], ['JM', 'Jamaica', '+1-876'],
            ['JP', 'Japan', '+81'], ['JO', 'Jordan', '+962'], ['KZ', 'Kazakhstan', '+7'],
            ['KE', 'Kenya', '+254'], ['KI', 'Kiribati', '+686'], ['KP', 'North Korea', '+850'],
            ['KR', 'South Korea', '+82'], ['KW', 'Kuwait', '+965'], ['KG', 'Kyrgyzstan', '+996'],
            ['LA', 'Laos', '+856'], ['LV', 'Latvia', '+371'], ['LB', 'Lebanon', '+961'],
            ['LS', 'Lesotho', '+266'], ['LR', 'Liberia', '+231'], ['LY', 'Libya', '+218'],
            ['LI', 'Liechtenstein', '+423'], ['LT', 'Lithuania', '+370'], ['LU', 'Luxembourg', '+352'],
            ['MG', 'Madagascar', '+261'], ['MW', 'Malawi', '+265'], ['MY', 'Malaysia', '+60'],
            ['MV', 'Maldives', '+960'], ['ML', 'Mali', '+223'], ['MT', 'Malta', '+356'],
            ['MH', 'Marshall Islands', '+692'], ['MR', 'Mauritania', '+222'], ['MU', 'Mauritius', '+230'],
            ['MX', 'Mexico', '+52'], ['FM', 'Micronesia', '+691'], ['MD', 'Moldova', '+373'],
            ['MC', 'Monaco', '+377'], ['MN', 'Mongolia', '+976'], ['ME', 'Montenegro', '+382'],
            ['MA', 'Morocco', '+212'], ['MZ', 'Mozambique', '+258'], ['MM', 'Myanmar', '+95'],
            ['NA', 'Namibia', '+264'], ['NR', 'Nauru', '+674'], ['NP', 'Nepal', '+977'],
            ['NL', 'Netherlands', '+31'], ['NZ', 'New Zealand', '+64'], ['NI', 'Nicaragua', '+505'],
            ['NE', 'Niger', '+227'], ['NG', 'Nigeria', '+234'], ['MK', 'North Macedonia', '+389'],
            ['NO', 'Norway', '+47'], ['OM', 'Oman', '+968'], ['PK', 'Pakistan', '+92'],
            ['PW', 'Palau', '+680'], ['PS', 'Palestine', '+970'], ['PA', 'Panama', '+507'],
            ['PG', 'Papua New Guinea', '+675'], ['PY', 'Paraguay', '+595'], ['PE', 'Peru', '+51'],
            ['PH', 'Philippines', '+63'], ['PL', 'Poland', '+48'], ['PT', 'Portugal', '+351'],
            ['QA', 'Qatar', '+974'], ['RO', 'Romania', '+40'], ['RU', 'Russia', '+7'],
            ['RW', 'Rwanda', '+250'], ['KN', 'Saint Kitts and Nevis', '+1-869'], ['LC', 'Saint Lucia', '+1-758'],
            ['VC', 'Saint Vincent and the Grenadines', '+1-784'], ['WS', 'Samoa', '+685'], ['SM', 'San Marino', '+378'],
            ['ST', 'Sao Tome and Principe', '+239'], ['SA', 'Saudi Arabia', '+966'], ['SN', 'Senegal', '+221'],
            ['RS', 'Serbia', '+381'], ['SC', 'Seychelles', '+248'], ['SL', 'Sierra Leone', '+232'],
            ['SG', 'Singapore', '+65'], ['SK', 'Slovakia', '+421'], ['SI', 'Slovenia', '+386'],
            ['SB', 'Solomon Islands', '+677'], ['SO', 'Somalia', '+252'], ['ZA', 'South Africa', '+27'],
            ['SS', 'South Sudan', '+211'], ['ES', 'Spain', '+34'], ['LK', 'Sri Lanka', '+94'],
            ['SD', 'Sudan', '+249'], ['SR', 'Suriname', '+597'], ['SE', 'Sweden', '+46'],
            ['CH', 'Switzerland', '+41'], ['SY', 'Syria', '+963'], ['TJ', 'Tajikistan', '+992'],
            ['TZ', 'Tanzania', '+255'], ['TH', 'Thailand', '+66'], ['TL', 'Timor-Leste', '+670'],
            ['TG', 'Togo', '+228'], ['TO', 'Tonga', '+676'], ['TT', 'Trinidad and Tobago', '+1-868'],
            ['TN', 'Tunisia', '+216'], ['TR', 'Turkey', '+90'], ['TM', 'Turkmenistan', '+993'],
            ['TV', 'Tuvalu', '+688'], ['UG', 'Uganda', '+256'], ['UA', 'Ukraine', '+380'],
            ['AE', 'United Arab Emirates', '+971'], ['GB', 'United Kingdom', '+44'], ['US', 'United States', '+1'],
            ['UY', 'Uruguay', '+598'], ['UZ', 'Uzbekistan', '+998'], ['VU', 'Vanuatu', '+678'],
            ['VA', 'Vatican City', '+379'], ['VE', 'Venezuela', '+58'], ['VN', 'Vietnam', '+84'],
            ['YE', 'Yemen', '+967'], ['ZM', 'Zambia', '+260'], ['ZW', 'Zimbabwe', '+263'],
        ];

        $countryMap = [];
        foreach ($countries as [$iso2, $name, $phone]) {
            $c = Country::firstOrCreate(['iso2' => $iso2], ['name' => $name, 'phone_code' => $phone]);
            $countryMap[$iso2] = $c->id;
        }

        // Major regions / cities by country ISO2
        $regionsByCountry = [
            'KE' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Kiambu', 'Machakos', 'Uasin Gishu'],
            'UG' => ['Kampala', 'Wakiso', 'Entebbe', 'Jinja', 'Mbarara', 'Gulu', 'Mbale'],
            'RW' => ['Kigali', 'Northern Province', 'Southern Province', 'Eastern Province', 'Western Province'],
            'BI' => ['Bujumbura Mairie', 'Gitega', 'Ngozi', 'Muyinga', 'Rumonge'],
            'CD' => ['Kinshasa', 'Lubumbashi', 'Goma', 'Bukavu', 'Kisangani'],
            'ZM' => ['Lusaka', 'Copperbelt', 'Southern', 'Eastern', 'Northern'],
            'MW' => ['Lilongwe', 'Blantyre', 'Mzuzu', 'Zomba'],
            'MZ' => ['Maputo', 'Beira', 'Nampula', 'Pemba', 'Tete'],
            'ZA' => ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Limpopo'],
            'NG' => ['Lagos', 'Abuja (FCT)', 'Kano', 'Rivers', 'Oyo'],
            'GH' => ['Greater Accra', 'Ashanti', 'Western', 'Northern'],
            'ET' => ['Addis Ababa', 'Oromia', 'Amhara', 'Tigray', 'Sidama'],
            'EG' => ['Cairo', 'Alexandria', 'Giza', 'Luxor', 'Aswan'],
            'AE' => ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman'],
            'GB' => ['London', 'Manchester', 'Birmingham', 'Leeds', 'Glasgow'],
            'US' => ['New York', 'California', 'Texas', 'Florida', 'Illinois'],
            'IN' => ['Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu', 'Gujarat'],
            'CN' => ['Beijing', 'Shanghai', 'Guangdong', 'Zhejiang', 'Sichuan'],
        ];

        foreach ($regionsByCountry as $iso2 => $regionNames) {
            if (! isset($countryMap[$iso2])) {
                continue;
            }
            $cid = $countryMap[$iso2];
            foreach ($regionNames as $rName) {
                Region::firstOrCreate(['country_id' => $cid, 'name' => $rName]);
            }
        }

        // All 31 Regions of Tanzania & their Districts (Districts seeded ONLY for Tanzania)
        $tanzaniaRegionsAndDistricts = [
            'Arusha' => ['Arusha City', 'Arusha District', 'Karatu', 'Longido', 'Meru', 'Monduli', 'Ngorongoro'],
            'Dar es Salaam' => ['Ilala', 'Kinondoni', 'Temeke', 'Ubungo', 'Kigamboni'],
            'Dodoma' => ['Dodoma City', 'Bahi', 'Chamwino', 'Chemba', 'Kondoa', 'Kongwa', 'Mpwapwa'],
            'Geita' => ['Bukombe', 'Chato', 'Geita Town', 'Geita District', 'Mbogwe', 'Nyang\'hwale'],
            'Iringa' => ['Iringa Municipal', 'Iringa District', 'Kilolo', 'Mafinga Town', 'Mufindi'],
            'Kagera' => ['Biharamulo', 'Bukoba Municipal', 'Bukoba District', 'Karagwe', 'Kyerwa', 'Missenyi', 'Muleba', 'Ngara'],
            'Katavi' => ['Mlele', 'Mpanda Municipal', 'Mpimbwe', 'Nsimbo', 'Tanganyika'],
            'Kigoma' => ['Buhigwe', 'Kakonko', 'Kasulu Town', 'Kasulu District', 'Kibondo', 'Kigoma-Ujiji Municipal', 'Kigoma District', 'Uvinza'],
            'Kilimanjaro' => ['Hai', 'Moshi Municipal', 'Moshi District', 'Mwanga', 'Rombo', 'Same', 'Siha'],
            'Lindi' => ['Kilwa', 'Lindi Municipal', 'Mtama', 'Liwale', 'Nachingwea', 'Ruangwa'],
            'Manyara' => ['Babati Town', 'Babati District', 'Hanang', 'Kiteto', 'Mbulu Town', 'Mbulu District', 'Simanjiro'],
            'Mara' => ['Bunda Town', 'Bunda District', 'Butiama', 'Musoma Municipal', 'Musoma District', 'Rorya', 'Serengeti', 'Tarime Town', 'Tarime District'],
            'Mbeya' => ['Busokelo', 'Chunya', 'Kyela', 'Mbarali', 'Mbeya City', 'Mbeya District', 'Rungwe'],
            'Morogoro' => ['Gairo', 'Ifakara Town', 'Kilombero', 'Kilosa', 'Malinyi', 'Morogoro Municipal', 'Morogoro District', 'Mvomero', 'Ulanga'],
            'Mtwara' => ['Masasi Town', 'Masasi District', 'Mtwara Municipal', 'Mtwara District', 'Nanyumbu', 'Newala Town', 'Newala District', 'Tandahimba'],
            'Mwanza' => ['Ilemela', 'Kwimba', 'Magu', 'Misungwi', 'Nyamagana', 'Sengerema', 'Ukerewe'],
            'Njombe' => ['Ludewa', 'Makambako Town', 'Makete', 'Njombe Town', 'Njombe District', 'Wanging\'ombe'],
            'Pemba North' => ['Micheweni', 'Wete'],
            'Pemba South' => ['Chake Chake', 'Mkoani'],
            'Pwani' => ['Bagamoyo', 'Chalinze', 'Kibaha Town', 'Kibaha District', 'Kibiti', 'Kisarawe', 'Mafia', 'Mkuranga', 'Rufiji'],
            'Rukwa' => ['Kalambo', 'Nkasi', 'Sumbawanga Municipal', 'Sumbawanga District'],
            'Ruvuma' => ['Mbinga Town', 'Mbinga District', 'Namtumbo', 'Nyasa', 'Songea Municipal', 'Songea District', 'Tunduru'],
            'Shinyanga' => ['Kahama Municipal', 'Kishapu', 'Msalala', 'Shinyanga Municipal', 'Shinyanga District', 'Ushetu'],
            'Simiyu' => ['Bariadi Town', 'Bariadi District', 'Busega', 'Itilima', 'Maswa', 'Meatu'],
            'Singida' => ['Ikungi', 'Iramba', 'Itigi', 'Manyoni', 'Mkalama', 'Singida Municipal', 'Singida District'],
            'Songwe' => ['Ileje', 'Mbozi', 'Momba', 'Songwe', 'Tunduma Town'],
            'Tabora' => ['Igunga', 'Kaliua', 'Nzega Town', 'Nzega District', 'Sikonge', 'Tabora Municipal', 'Urambo', 'Uyui'],
            'Tanga' => ['Bumbuli', 'Handeni Town', 'Handeni District', 'Kilindi', 'Korogwe Town', 'Korogwe District', 'Lushoto', 'Mkinga', 'Muheza', 'Pangani', 'Tanga City'],
            'Unguja North' => ['Kaskazini A', 'Kaskazini B'],
            'Unguja South' => ['Kati', 'Kusini'],
            'Mjini Magharibi' => ['Magharibi A', 'Magharibi B', 'Mjini'],
        ];

        $tzId = $countryMap['TZ'];
        foreach ($tanzaniaRegionsAndDistricts as $regionName => $districts) {
            $region = Region::firstOrCreate(['country_id' => $tzId, 'name' => $regionName]);
            foreach ($districts as $districtName) {
                District::firstOrCreate(['region_id' => $region->id, 'name' => $districtName]);
            }
        }
    }
}
