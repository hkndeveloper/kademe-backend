<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use Illuminate\Support\Str;

class ProjectRichDataSeeder extends Seeder
{
    public function run()
    {
        $projects = [
            [
                'name' => 'Pergel Fellowship',
                'sub_description' => 'Geleceğin Liderleri İçin Stratejik Gelişim Programı',
                'description' => 'Pergel Fellowship, üniversite öğrencilerinin entelektüel derinlik kazanmalarını, stratejik düşünme becerilerini geliştirmelerini ve küresel meselelere disiplinler arası bir bakış açısıyla yaklaşmalarını sağlayan prestijli bir programdır.',
                'location' => 'Konya / Hibrit',
                'capacity' => 40,
                'format' => 'Hibrit',
                'period' => '2024 - 2025 Güz',
                'timeline' => [
                    ['label' => 'Başvuruların Alınması', 'date' => 'Ekim 2024'],
                    ['label' => 'Mülakat ve Seçim Süreci', 'date' => 'Kasım 2024'],
                    ['label' => 'Program Açılış Lansmanı', 'date' => 'Aralık 2024'],
                    ['label' => 'Stratejik Düşünme Modülü', 'date' => 'Ocak 2025'],
                    ['label' => 'Saha Ziyaretleri ve Kapanış', 'date' => 'Mayıs 2025'],
                ],
                'documents' => [
                    ['title' => 'Program Katılım Kılavuzu', 'url' => '#'],
                    ['title' => 'Müfredat ve Okuma Listesi', 'url' => '#'],
                ],
            ],
            [
                'name' => 'KPD (Kariyer Psikolojik Danışmanlık)',
                'sub_description' => 'Potansiyelini Keşfet, Kariyerini Planla',
                'description' => 'Kariyer Psikolojik Danışmanlık programı, gençlerin kendilerini tanımasını, yeteneklerini keşfetmesini ve doğru kariyer hedefleri belirlemesini psikolojik testler ve profesyonel görüşmelerle destekleyen bir rehberlik sistemidir.',
                'location' => 'Online Seanslar',
                'capacity' => 100,
                'format' => 'Online',
                'period' => 'Yıl Boyunca Aktif',
                'timeline' => [
                    ['label' => 'Kişilik Envanter Uygulaması', 'date' => '1. Hafta'],
                    ['label' => 'Bireysel Kariyer Analizi', 'date' => '2. Hafta'],
                    ['label' => 'Uzman Danışman Randevusu', 'date' => '3. Hafta'],
                    ['label' => 'Gelişim Planı Teslimi', 'date' => '4. Hafta'],
                ],
                'documents' => [
                    ['title' => 'Test Uygulama Rehberi', 'url' => '#'],
                    ['title' => 'Örnek Kariyer Haritası', 'url' => '#'],
                ],
            ],
            [
                'name' => 'Eurodesk',
                'sub_description' => 'Avrupa Fırsatlarına Açılan Kapınız',
                'description' => 'Eurodesk, gençlerin Avrupa genelindeki eğitim, staj ve gönüllülük fırsatlarına erişimini sağlayan uluslararası bir bilgi ağıdır. KADEME bünyesinde gençlerin kariyer basamaklarını küresel ölçekte tırmanmalarına rehberlik ediyoruz.',
                'location' => 'Uluslararası / Ofis',
                'capacity' => 200,
                'format' => 'Yüz Yüze',
                'period' => 'Sürekli Kayıt',
                'timeline' => [
                    ['label' => 'Bilgilendirme Seminerleri', 'date' => 'Her Ayın İlk Cuma'],
                    ['label' => 'ESC Başvuru Atölyesi', 'date' => 'Her Ayın Son Salı'],
                    ['label' => 'Erasmus+ Rehberliği', 'date' => 'Randevu İle'],
                ],
                'documents' => [
                    ['title' => 'Erasmus+ Başvuru Kontrol Listesi', 'url' => '#'],
                    ['title' => 'Motivasyon Mektubu Yazım Rehberi', 'url' => '#'],
                ],
            ],
            [
                'name' => 'Diplomasi360',
                'sub_description' => 'Uluslararası İlişkilerde Pratik ve Teori',
                'description' => 'Diplomasi360, gençlerin diplomasi, protokol kuralları ve uluslararası ilişkiler alanında yetkinlik kazanmalarını sağlayan simülasyon temelli bir eğitim programıdır.',
                'location' => 'Konya / Bölge Ofisleri',
                'capacity' => 60,
                'format' => 'Yüz Yüze',
                'period' => '2024 Güz',
                'timeline' => [
                    ['label' => 'Diplomatik Yazışma Eğitimi', 'date' => 'Hafta 1'],
                    ['label' => 'Kriz Yönetimi Simülasyonu', 'date' => 'Hafta 4'],
                    ['label' => 'Kurumsal Ziyaretler', 'date' => 'Hafta 6'],
                    ['label' => 'Final Sertifika Töreni', 'date' => 'Hafta 12'],
                ],
                'documents' => [
                    ['title' => 'Simülasyon El Kitabı', 'url' => '#'],
                    ['title' => 'Protokol Kuralları Dosyası', 'url' => '#'],
                ],
            ],
            [
                'name' => 'KADEME+',
                'sub_description' => 'Sürekli Öğrenme ve Gelişim Platformu',
                'description' => 'KADEME+, mevcut programlarımızın ötesinde, katılımcılara ek yetkinlikler kazandıran dijital ve fiziksel atölye serilerinden oluşan bir gelişim ekosistemidir.',
                'location' => 'Hibrit / Dijital',
                'capacity' => 150,
                'format' => 'Hibrit',
                'period' => 'Akademik Takvim',
                'timeline' => [
                    ['label' => 'Atölye Seçim Süreci', 'date' => 'Eylül'],
                    ['label' => 'Uygulamalı Eğitimler', 'date' => 'Ekim - Mart'],
                    ['label' => 'Proje Teslimleri', 'date' => 'Nisan'],
                    ['label' => 'KADEME+ Zirvesi', 'date' => 'Haziran'],
                ],
                'documents' => [
                    ['title' => 'Kişisel Gelişim Karnesi', 'url' => '#'],
                    ['title' => 'Dijital Rozet Kılavuzu', 'url' => '#'],
                ],
            ],
        ];

        foreach ($projects as $data) {
            Project::updateOrCreate(
                ['name' => $data['name']],
                array_merge($data, [
                    'slug' => Str::slug($data['name']),
                    'is_active' => true,
                ])
            );
        }
    }
}
