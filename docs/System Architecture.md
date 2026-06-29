**Architecture Decision Record (ADR-001)**

**Architecture Style:**\
Modular Monolith\
\
**Application Layer:**\
Action-Based - do **not** want business logic inside controllers.\
\
**Communication:**\
Events + Listeners\
\
**Authorization:**\
Policies, Access Model (RBAC)\
\
**Realtime:**\
Event-driven Broadcasting\
\
**Deployment:**\
Single Laravel Application

**Event Architecture (ADR-002)**

**Architecture Style:**\
Event-Driven\
\
**Core Events:**\
Workspace Events\
Board Events\
Task Events -- TaskCreated, TaskMoved, TaskAssigned\
Comment Events -- CommentAdded\
\
**Activity Creation:**\
Event Listeners\
\
**Notification Creation:**\
Event Listeners\
\
**Broadcasting:**\
Event Listeners\
\
**Heavy Work:**\
Queued\
\
**Business Logic:**\
Actions

**Realtime Architecture (ADR-003)**

**Transport:**

Laravel Reverb

**Message Broker:**

Redis

**Frontend:**

Laravel Echo + Vue

**Channels:**

Workspace

Board

User

**Presence:**

Yes

**UI Updates:**

Optimistic

**Broadcast Source:**

Event Listeners

**Reconnect Strategy:**

Reconnect + Refetch

**OT:**

No

**CRDT:**

No

**Final Concurrency Decision (ADR-004)**

Concurrency Strategy

**Text Collaboration:**

No OT

No CRDT

**Conflict Resolution:**

Optimistic Concurrency Control

**Entity Versioning:**

Yes

**Conflict Response:**

HTTP 409

**Task Ordering:**

Position-Based Ordering

**Drag Updates:**

Persist On Drop

**Frontend:**

Throttle High-Frequency Actions

**Presence:**

Informational Only

**Deleted Records:**

Return 404
