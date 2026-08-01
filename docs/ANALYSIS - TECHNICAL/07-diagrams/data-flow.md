# Data Flow Diagram

```mermaid
flowchart LR
    Client --> Booking
    Booking --> Payment
    Payment --> Gateway[PayMongo or Stripe]
    Gateway --> Payment
    Booking --> Assignment[Photographer assignment]
    Assignment --> Gallery
    Gallery --> Client
    Client --> Review
    Review --> StudioOrFreelancer[Studio or freelancer]
```
