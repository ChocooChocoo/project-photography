# Process Flows

> **In plain terms:** These diagrams show the current paths through booking, payment, and galleries. They do not add missing policy steps.

## Booking

```mermaid
flowchart TD
    A[Client selects studio or freelancer] --> B[Client completes booking form]
    B --> C[Booking record]
    C --> D[Payment initialization]
    D --> E[Payment confirmation or webhook]
    E --> F[Booking and payment records update]
```

## Studio delivery

```mermaid
flowchart TD
    A[Owner assigns photographer] --> B[Photographer handles assignment]
    B --> C[Gallery uploaded as draft]
    C --> D[Owner publishes gallery]
    D --> E[Client views published gallery]
```

## Freelancer delivery

```mermaid
flowchart TD
    A[Client books freelancer] --> B[Freelancer performs service]
    B --> C[Freelancer uploads gallery]
    C --> D[Client views published gallery]
```
