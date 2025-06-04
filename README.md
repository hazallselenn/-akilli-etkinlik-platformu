# Akıllı Etkinlik Platformu

## Proje Hakkında

Akıllı Etkinlik Platformu, etkinlik yönetimi için geliştirilen modern bir web uygulamasıdır.  
Proje; kullanıcı yönetimi, etkinlik oluşturma, görsel ve metin analizi, güvenlik önlemleri ve yetkilendirme gibi modüller içermektedir.

---

## Özellikler ve Sorumlular

| Özellik                                    | Sorumlu      | Branch İsmi           |
|--------------------------------------------|--------------|----------------------|
| Presentation Layer (UI Framework)           | Hazal Selen  | feature-hazal         |
| Business Layer (OOP Components)              | Hazal Selen  | feature-hazal         |
| Data Layer (ORM / Migrations)                | Hazal Selen  | feature-hazal         |
| Web Service Implementation (RESTful API)    | Hazal Selen  | feature-hazal         |
| RBAC Implementation (Role-Based Access Control) | Hazal Selen  | feature-hazal         |
| Authorization Implementation (JWT Token)    | Kerim Doğan  | feature-kerim         |
| Session / Cookie Management                   | Kerim Doğan  | feature-kerim         |
| Extension / Third Party Library Integration   | Kerim Doğan  | feature-kerim         |
| Web Security Implementation (CSRF Koruması)  | Kerim Doğan  | feature-kerim         |
| Cloud Service Integration (Google Cloud NLP & Vision API) | Kerim Doğan  | feature-kerim         |

---

## Kurulum ve Çalıştırma

1. Depoyu klonlayın:
   ```bash
   git clone https://github.com/hazallselenn/-akilli-etkinlik-platformu.git
   cd -akilli-etkinlik-platformu
   docker compose-up
