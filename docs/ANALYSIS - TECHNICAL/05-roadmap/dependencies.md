# Dependencies

> **In plain terms:** Approved work moves from a user-authored request through requirements, implementation, proof, and a progress update. The two open policy questions block only the implementations that depend on their answers.

```mermaid
flowchart LR
    A[User-authored prompt] --> B[Requirements and task record]
    B --> C[Approved implementation]
    C --> D[Tests and evidence]
    D --> E[Progress update]
    Q1[QST-001 cancellation policy] --> C
    Q2[QST-002 subscription policy] --> C
```

Only policy-dependent implementation is blocked by `QST-001` or `QST-002`; analysis records are complete.
