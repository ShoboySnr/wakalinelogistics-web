<?php

namespace App\Modules\DeliveryCalculator\Helpers;

class ZoneDetector
{
    private static array $zones = [
        'Zone A' => [
            // Ikeja & Environs
            'Ikeja', 'Alausa', 'Allen', 'Opebi', 'Oregun', 'Toyin', 'Awolowo Way', 'Obafemi Awolowo',
            'Mobolaji Bank Anthony', 'Acme', 'Anifowoshe', 'Adeniyi Jones', 'GRA Ikeja', 'Ikeja GRA',
            'Kay Farms', 'Kayfarm', 'Kay Farm', 'Rotimi Williams', 'Chief Rotimi Williams',
            'Computer Village', 'Oba Akran', 'Kudirat Abiola',
            // Agege & Environs
            'Agege', 'Dopemu', 'Pen Cinema', 'Oke-Koto', 'Orile Agege', 'Mangoro', 'Cement', 'Abattoir',
            // Ogba & Environs
            'Ogba', 'Wempco', 'Acme Road', 'Aguda', 'Omole Phase 1', 'Omole Phase 2', 'Omole',
            // Iju & Environs
            'Iju', 'Iju Ishaga', 'Iju Fagba', 'Ifako', 'Ijaiye', 'Fagba', 'Abule Egba',
            // Ojokoro & Environs
            'Ojokoro', 'Isheri Olofin', 'Oke Ira', 'Agbado', 'Akute', 'Lambe', 'Ajuwon',
            // Alimosho Areas
            'Akowonjo', 'Shasha', 'Egbeda', 'Idimu', 'Ikotun', 'Igando', 'Isheri Osun', 'Iyana Ipaja',
            'Ayobo', 'Ipaja', 'Command', 'Meiran', 'Ekoro', 'Alagbado', 'Pleasure',
            'Powerline', 'Ile Epo', 'Oke Odo', 'Aboru', 'Gowon Estate'
        ],
        'Zone B' => [
            // Ketu & Environs
            'Ketu', 'Alapere', 'Tipper Garage', 'Demurin', 'Ikosi', 'Agboyi',
            // Ojota & Environs
            'Ojota', 'Ogudu', 'Ogudu GRA', 'Kosofe', 'Owode Onirin', 'Ojota Interchange',
            // Maryland & Environs
            'Maryland', 'Mende', 'Onigbongbo', 'Ikeja Along', 'Mafoluku',
            // Gbagada & Environs
            'Gbagada', 'Gbagada Phase 1', 'Gbagada Phase 2', 'Ifako Gbagada', 'New Garage', 'Soluyi',
            // Magodo & Environs
            'Magodo', 'Magodo Phase 1', 'Magodo Phase 2', 'Shangisha', 'CMD', 'Isheri', 'Isheri North',
            // Anthony & Environs
            'Anthony', 'Anthony Village', 'Palmgroove', 'Onipanu', 'Fadeyi', 'Jibowu',
            // Shomolu & Environs
            'Shomolu', 'Bariga', 'Akoka', 'Pedro', 'Oworonshoki', 'Somolu',
            // Ilupeju & Environs
            'Ilupeju', 'Coker', 'Mushin', 'Idi Araba', 'Papa Ajao', 'Ladipo',
            // Mile 12 & Environs
            'Mile 12', 'Ketu Mile 12', 'Owode', 'Maidan', 'Berger Yard'
        ],
        'Zone C' => [
            // Yaba & Environs
            'Yaba', 'Abule Ijesha', 'Makoko', 'Iwaya', 'Sabo Yaba', 'Tejuosho',
            // Surulere & Environs
            'Surulere', 'Itire', 'Lawanson', 'Ijeshatedo', 'Shitta', 'Adeniran Ogunsanya',
            'Bode Thomas', 'Randle', 'Aguda Surulere', 'Ojuelegba', 'Iponri', 'Costain',
            'Eric Moore', 'Alaka', 'Masha', 'Kilo', 'Barracks', 'Stadium', 'National Stadium',
            // Ebute Metta & Environs
            'Ebute Metta', 'Oyingbo', 'Yaba Tech', 'Otto', 'Ijora'
        ],
        'Zone D' => [
            // Festac & Amuwo Axis
            'Festac', 'Festac Town', 'Amuwo Odofin', 'Mile 2', 'Apple Junction',
            // Ago Palace
            'Ago Palace',
        ],
        'Zone E' => [
            // Ikoyi
            'Ikoyi', 'Old Ikoyi', 'Ikoyi Crescent', 'Bourdillon', 'Queens Drive', 'Kingsway',
            'Glover', 'Awolowo Road', 'Alexander', 'Banana Island', 'Parkview', 'Dolphin Estate',
            'Osborne', 'Foreshore', 'Ikoyi Link Bridge', 'Falomo', 'Obalende',
            // Victoria Island
            'Victoria Island', 'VI', 'Adeola Odeku', 'Ahmadu Bello', 'Akin Adesola',
            'Adetokunbo Ademola', 'Ozumba Mbadiwe', 'Idowu Taylor', 'Sanusi Fafunwa',
            'Ajose Adeogun', 'Kofo Abayomi', 'Adeola Hopewell', 'Ligali Ayorinde',
            'Bishop Aboyade Cole', 'Tiamiyu Savage', 'Oniru Estate', 'Water Corporation',
            'Bar Beach', 'Kuramo', 'Maroko', 'Oniru', 'Lekki Phase 1 Gate',
            // Lagos Island
            'Lagos Island', 'Marina', 'CMS', 'Broad Street', 'Onikan', 'Ebute Ero',
            'Idumota', 'Balogun', 'Tinubu', 'Adeniji Adele', 'Epetedo', 'Isale Eko',
            'Sandgrouse', 'Offin', 'Oko Awo', 'Oke Arin', 'Olowogbowo', 'Ereko',
            'Alakoro', 'Ita Faji', 'Lafiaji', 'Campos', 'Nnamdi Azikiwe', 'Apongbon',
            'Eko Bridge', 'Carter Bridge', 'Third Mainland Bridge', 'Ikoyi Bridge'
        ],
        'Zone F' => [
            // Lekki Phase 1
            'Lekki', 'Lekki Phase 1', 'Lekki 1', 'Admiralty Way', 'Admiralty', 'Marwa',
            'Ikate', 'Ikate Elegushi', 'Elegushi', 'Chisco', 'Jakande', 'Lekki Right',
            // Lekki Phase 2 & Nearby
            'Lekki Phase 2', 'Lekki 2', 'Ilasan', 'Igbo Efon', 'Idado', 'Agungi',
            'Osapa London', 'Osapa', 'Chisco Bus Stop', 'Oral Estate', 'Greensprings',
            // VGC & Environs
            'VGC', 'Victoria Garden City', 'Ikota', 'Ikota Villa', 'Ikota Complex',
            'Pinnock Beach', 'Eti Osa', 'Conservation Centre',
            // Chevron & Environs
            'Chevron', 'Chevron Drive', 'Chevron Roundabout', 'Igbo Efon Roundabout',
            'Orchid', 'Orchid Road', 'Ologolo', 'Jakande Lekki',
        ],
        'Zone G' => [
            // Sangotedo & Beyond
            'Sangotedo', 'Monastery', 'Novare Mall', 'Shoprite Sangotedo', 'Abijo',
            'Lakowe', 'Lakowe Lakes', 'Awoyaya', 'Majek', 'Shapati', 'Ibeju',
            // Epe & Far Lekki
            'Epe', 'Ibeju Lekki', 'Bogije', 'Eleko', 'Akodo', 'Magbon Alade',
            'Dangote Refinery', 'Dangote', 'Refinery', 'Free Trade Zone', 'Lekki Free Zone',
            'Idasho', 'Oriyanrin', 'Igbogun', 'Itoikin', 'Poka', 'Ejirin'
        ],
        'Zone H' => [
            // Ojodu & Berger (nearer interstate)
            'Ojodu', 'Akiode', 'Isheri Olowora',
            // Berger & Environs
            'Berger', 'Ojodu Berger', 'Kara',
            // Warewa / Arepo / Magboro axis
            'Warewa', 'Arepo', 'Magboro',
        ],
        'Zone I' => [
            // Ikorodu & Environs
            'Ikorodu', 'Ikorodu Town', 'Ikorodu Garage', 'Agric', 'Owutu', 'Igbogbo',
            'Ebute', 'Ijede', 'Erikorodo', 'Isiu', 'Baiyeku', 'Erunwen', 'Gberigbe',
            'Ipakodo', 'Majidun', 'Odogunyan', 'Sabo Ikorodu', 'Itamaga', 'Ewu Elepe',
            'Ibeshe', 'Imota', 'Agura', 'Ishawo', 'Ogolonto', 'Odonla', 'Odoragushin',
            'Ijede Ikorodu', 'Agbowa', 'Igbokuta', 'Maya', 'Parafa',
            // Ota & Environs
            'Ota', 'Joju', 'Atan', 'Agbara', 'Lusada',
            'Ijoko', 'Ilogbo', 'Iju Ota', 'Toll Gate',
            'Owode', 'Onipanu Ota', 'Itori', 'Ewekoro', 'Papalanto',
            // Ogijo & Environs
            'Ogijo', 'Odongunyan Ikorodu', 'Iperu', 'Makun'
        ],
        'Zone J' => [
            // Ojo & Satellite Town Axis
            'Satellite Town', 'Alaba', 'Alaba International', 'Ojo', 'Okokomaiko',
            // Badagry & Environs
            'Badagry', 'Ajangbadi', 'Ojo Alaba', 'Iyana Iba', 'Iba', 'Igbo Elerin', 'Agbara Badagry',
            'Topo', 'Ibereko', 'Ikoga', 'Aradagun', 'Mowo', 'Gbaji', 'Ajara',
        ],
        'Zone K' => [
            // Ajah & Environs
            'Ajah', 'Badore', 'Addo', 'Langbasa', 'Ado', 'Ajah Roundabout',
            'Abraham Adesanya', 'Lekki Gardens', 'Coscharis', 'Mega Chicken',
            // Alpha Beach & Environs
            'Alpha Beach', 'Alpha Beach Road', 'Igbo Efon Lekki', 'Okun Ajah',
        ],
        'Zone L' => [
            // Oshodi & Environs
            'Oshodi', 'Bolade', 'Shogunle', 'Oshodi Oke',
            // Mushin inner
            'Idi Oro', 'Olateju', 'Alakara', 'Odi Olowo',
            // Isolo & Environs
            'Isolo', 'Okota', 'Ejigbo', 'Jakande', 'Isolo Road', 'Oke Afa',
            'Bucknor', 'Cele', 'Ijesha', 'Ire Akari', 'Ajao Estate', 'Aswani',
            // Apapa & Environs
            'Apapa', 'Ajegunle', 'Kirikiri', 'Boundary', 'Orile', 'Iganmu', 'Liverpool', 'Tincan',
            'Wharf', 'Port', 'Apapa Wharf', 'Apapa Road', 'Creek Road', 'Point Road',
        ],
    ];

    public static function detectZone(string $address): string
    {
        $address = strtolower($address);

        foreach (self::$zones as $zoneName => $locations) {
            foreach ($locations as $location) {
                if (str_contains($address, strtolower($location))) {
                    return $zoneName;
                }
            }
        }

        if (str_contains($address, 'lagos') && !str_contains($address, 'ogun')) {
            return 'Zone C';
        }

        return 'Unknown Zone';
    }

    public static function isMainland(string $zone): bool
    {
        return in_array($zone, ['Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone H', 'Zone I', 'Zone J', 'Zone L']);
    }

    public static function isIsland(string $zone): bool
    {
        return in_array($zone, ['Zone E', 'Zone F', 'Zone G', 'Zone K']);
    }

    public static function getAllZones(): array
    {
        return self::$zones;
    }
}
