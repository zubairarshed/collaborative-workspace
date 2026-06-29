
# Data Models

The goal is to define:

Entities\
Relationships\
Cardinality\
Ownership\
Constraints

**1. User**

Represents a platform user.

Relationships:

User\
├─ hasMany Memberships\
├─ hasMany Comments\
├─ hasMany Activities\
├─ hasMany Notifications\
└─ belongsToMany Tasks (assignee)

**2. Workspace**

Top-level collaboration boundary.

Relationships:

Workspace\
├─ hasMany Memberships\
├─ hasMany Boards\
├─ hasMany Invitations\
└─ hasMany Activities

Constraint:

Workspace cannot exist without an Owner.

**3. Membership**

Connects User and Workspace.

Represents:

User\
↔\
Workspace

with role information.

Relationships:

Membership\
├─ belongsTo User\
└─ belongsTo Workspace

Constraint:

One user can have only one membership\
per workspace.

**4. Board**

Project container.

Relationships:

Board\
├─ belongsTo Workspace\
└─ hasMany Columns

Constraint:

Board must belong to exactly one workspace.

**5. Column**

Workflow stage.

Relationships:

Column\
├─ belongsTo Board\
└─ hasMany Tasks

Examples:

Todo\
Doing\
Review\
Done

Constraint:

Column names should be unique within board.

**6. Task**

Core business entity.

Relationships:

Task\
├─ belongsTo Column\
├─ hasMany Comments\
├─ hasMany Activities\
├─ belongsToMany Users (assignees)\
└─ belongsToMany Labels

Constraint:

Task belongs to exactly one column.

**7. Comment**

Discussion around work.

Relationships:

Comment\
├─ belongsTo Task\
└─ belongsTo User

Constraint:

Comment cannot exist without task.

**8. Activity**

Audit/history record.

Examples:

Task Created\
Task Moved\
Comment Added\
Member Invited

Relationships:

Activity\
├─ belongsTo Workspace\
├─ belongsTo User\
└─ references target entity

## **Important Design Choice**

I recommend a polymorphic target.

Example:

Activity\
↓\
\
Task\
Comment\
Board\
Workspace

Laravel:

activityable

This avoids:

task_id\
comment_id\
board_id\
workspace_id

all in one table.

------------------------------------------------------------------------

# 9. Notification

Represents attention-required events.

Relationships:

Notification\
└─ belongsTo User

Examples:

Assigned task\
Mentioned in comment\
Invitation received

------------------------------------------------------------------------

# 10. Invitation

Used before membership exists.

Relationships:

Invitation\
└─ belongsTo Workspace

After acceptance:

Invitation\
↓\
Membership

Constraint:

Cannot invite existing member.

------------------------------------------------------------------------

# 11. Label

Task categorization.

Examples:

Bug\
Feature\
Backend\
Urgent

Relationships:

Label\
├─ belongsTo Workspace\
└─ belongsToMany Tasks

------------------------------------------------------------------------

# Many-to-Many Relationships

We currently have only two.

## Task Assignments

Task\
↔\
User

Pivot:

task_user

------------------------------------------------------------------------

## Task Labels

Task\
↔\
Label

Pivot:

label_task

(or label_task, task_label; naming can be decided later)

# Aggregate Ownership

This is a DDD-ish concept but useful.

Think:

Workspace\
owns\
Boards\
Memberships\
Invitations\
\
Board\
owns\
Columns\
\
Column\
owns\
Tasks\
\
Task\
owns\
Comments

Meaning:

Delete Workspace\
↓\
Delete Boards\
↓\
Delete Columns\
↓\
Delete Tasks

# Invariants (Business Rules)

These are very important.

### Workspace

Must always have exactly one owner.

------------------------------------------------------------------------

### Membership

One membership per user per workspace.

------------------------------------------------------------------------

### Board

Must belong to a workspace.

------------------------------------------------------------------------

### Task

Must belong to a column.

------------------------------------------------------------------------

### Assignment

Assigned user must belong to same workspace.

------------------------------------------------------------------------

### Comment

Author must belong to workspace.

# Domain Entities

# **Domain Entities**

A domain entity is a thing that the business cares about and can
identify uniquely.

Or

An Entity is something with:

- Identity

- State

- Behavior

i.e., User, Workspace, Task, Board etc. These have a lifecycle. A task
has an **identity** (task#123), **state** (title, description, due date,
status), **behavior** (move(), assign(), complete(), archive()).
Therefore, Task = Domain Entity.

**Core Domain**

- User

- Workspace

- Membership

- Board

- Column

- Task

- Comment

- Activity

- Notification

- Invitation

- Label

### Future Domain

- Attachment

- Checklist

- ChecklistItem

- Reminder

- Template

# FRs

**Business Domains**

**FR-1 Workspace Management -- Team Boundary**

- Create workspace

- Update workspace

- Delete workspace

- Invite members

- Remove members

- Manage roles

### FR-2 Board Management -- Project Container

- Create board

- Update board

- Archive board

- View board

### FR-3 Task Management -- Unit of Work

- Create task

- Update task

- Move task

- Assign task

- Delete task

- Add labels

- Add due dates

### Collaboration Domains

### FR-4 Collaboration -- Communication around Work

- Comment on tasks

- Mention users

- View activity history

- Receive notifications

### 

### 

### 

### FR-5 Real-Time Collaboration -- Everyone sees changes Instantly

- See task movement instantly

- Receive notifications instantly

- See online users

- See typing indicators

### Platform Domains

### FR-6 Search -- Access Information Quickly

- Search tasks

- Search boards

- Search users

### FR-7 Security -- Who can do What

- Authentication

- Authorization

- Role-based access

### FR-8 Reliability -- Correct results even under Stress

- Prevent duplicate actions

- Handle concurrent updates

- Maintain activity history

# Implementation workflow

Sprint 1:

Workspace + Membership + Invitations

Sprint 2:

Boards + Columns

Sprint 3:

Tasks

Sprint 4:

Activity Feed

Sprint 5:

Notifications

Sprint 6:

Realtime

Sprint 7:

Presence

Sprint 8:

Concurrency Handling

# NFRs

**Non-Functional Requirements (NFRs)**

**NFR-1 Performance**

- Initial board load should complete within 2 seconds under normal
  conditions.

- Task creation, update, and movement operations should complete within
  500ms.

- Search results should be returned within 500ms.

- Activity feeds and notifications should load within 1 second.

**NFR-2 Real-Time Responsiveness**

- Task movements should be visible to connected users within 1 second.

- New comments should appear to connected users within 1 second.

- Presence information (online users) should update within 5 seconds.

- Typing indicators should update within 500ms.

**NFR-3 Scalability**

The system should comfortably support:

- 100 concurrent authenticated users.

- 50 active users viewing the same board.

- 100 workspaces.

- 1000 tasks per board.

- 100 comments per task.

The architecture should allow future horizontal scaling without major
redesign.

**NFR-4 Reliability**

- Concurrent task updates must not corrupt data.

- Duplicate requests should not create duplicate records.

- Task ordering must remain consistent after concurrent movements.

- Activity logs must accurately reflect user actions.

- System failures should not leave partially completed operations.

**NFR-5 Availability**

- Temporary websocket disconnections should not require page refreshes.

- Clients should automatically reconnect to real-time services.

- Users should continue viewing cached board data during brief
  connection interruptions.

**NFR-6 Security**

- All protected actions require authentication.

- All sensitive actions require authorization checks.

- Users may only access workspaces they belong to.

- File uploads must be validated.

- User input must be sanitized and validated.

- Rate limiting should protect public endpoints.

**NFR-7 Maintainability**

- Business logic must not reside in controllers.

- Features should be organized by domain.

- Core actions should be independently testable.

- The codebase should support adding new collaboration features without
  major refactoring.

**NFR-8 Observability**

- Important business actions must be logged.

- Errors must be recorded for troubleshooting.

- User activity should be traceable through an activity feed.

- Administrative audit information should be available for critical
  actions.

**NFR-9 Consistency**

- Clients should converge to the same board state after updates.

- Stale updates must not overwrite newer data.

- Event ordering must be preserved where required.

- Board state should remain valid after simultaneous user actions.

**NFR-10 Resource Efficiency**

- High-frequency frontend events must be throttled.

- Expensive operations should be processed asynchronously through
  queues.

- Frequently accessed data should be cacheable.

- Real-time broadcasts should be minimized to necessary recipients.

# System Architecture

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

# Use Cases

**UC-01 Create Workspace**

**Actor**

Authenticated User

**Goal**

Create a new workspace to collaborate with others.

**Main Flow**

1\. User enters workspace name.\
2. System creates workspace.\
3. User becomes Owner.\
4. Default settings are created.

**Business Rules**

Workspace name required.\
Creator automatically becomes Owner.

**Outcome**

Workspace exists and can accept members.

# UC-02 Invite Member

### Actor

Owner, Admin

### Goal

Add new collaborators to a workspace.

### Main Flow

1\. Actor enters email.\
2. Actor selects role.\
3. Invitation is created.\
4. Invitation notification/email sent.\
5. Invitee accepts.\
6. Membership created.

### Business Rules

Cannot invite existing member.\
Role required.\
Invitation expires after configured period.

### Outcome

New member joins workspace.

# UC-03 Create Board

### Actor

Owner, Admin, Member

### Goal

Create a board inside a workspace.

### Main Flow

1\. User enters board name.\
2. Board created.\
3. Default columns created.\
4. Activity recorded.

### Business Rules

Board belongs to workspace.\
Board name required.

### Outcome

Board ready for task management.

# UC-04 Create Task

### Actor

Owner, Admin, Member

### Goal

Create a new unit of work.

### Main Flow

1\. User selects column.\
2. User enters task information.\
3. Task created.\
4. Activity recorded.\
5. Board updates.

### Business Rules

Title required.\
Task must belong to board column.

### Outcome

Task becomes visible on board.

# UC-05 Move Task

### Actor

Owner, Admin, Member

### Goal

Change task workflow state or position.

### Main Flow

1\. User drags task.\
2. System validates permissions.\
3. Task position updated.\
4. Activity recorded.\
5. Real-time event broadcast.

### Business Rules

Target column must exist.\
Task ordering must remain valid.

### Outcome

All connected users see updated board.

### Future Technical Concerns

Race conditions\
Versioning\
Optimistic locking

# UC-06 Assign Task

### Actor

Owner, Admin, Member

### Goal

Assign responsibility for a task.

### Main Flow

1\. User selects one or more members.\
2. Assignment stored.\
3. Notifications generated.\
4. Activity recorded.

### Business Rules

Assignee must belong to workspace.\
Multiple assignees allowed.

### Outcome

Assigned users become responsible for task.

# UC-07 Add Comment

### Actor

Owner, Admin, Member

### Goal

Discuss work within a task.

### Main Flow

1\. User submits comment.\
2. Comment stored.\
3. Activity recorded.\
4. Mention notifications generated.\
5. Real-time event broadcast.

### Business Rules

Comment cannot be empty.

### Outcome

Discussion becomes visible to team.

# UC-08 View Activity Feed

### Actor

Owner, Admin, Member, Viewer

### Goal

Understand what has happened in workspace.

### Main Flow

1\. User opens activity feed.\
2. System retrieves activities.\
3. Activities displayed chronologically.

### Business Rules

Only activities within accessible workspace shown.

### Outcome

User gains visibility into project history.

# UC-09 Receive Notification

### Actor

System (triggered by user actions)

### Goal

Alert users about relevant events.

### Triggers

Task assignment\
Mentions\
Invitations\
Comments

### Main Flow

1\. Event occurs.\
2. Notification created.\
3. Notification delivered.\
4. User views notification.\
5. User marks notification as read.

### Business Rules

Notifications belong to a user.\
Duplicate notifications should be avoided.

### Outcome

User becomes aware of important activity.

# User roles and permissions

  ------------------------------------------------------------------
  **Permission**   **Owner**   **Admin**   **Member**   **Viewer**
  ---------------- ----------- ----------- ------------ ------------
  View Workspace   ✅          ✅          ✅           ✅

  Update Workspace ✅          ✅          ❌           ❌

  Delete Workspace ✅          ❌          ❌           ❌

  Invite Members   ✅          ✅          ❌           ❌

  Remove Members   ✅          ✅          ❌           ❌

  Change Roles     ✅          ✅          ❌           ❌

  Create Board     ✅          ✅          ✅           ❌

  Update Board     ✅          ✅          ✅           ❌

  Archive Board    ✅          ✅          ❌           ❌

  Create Task      ✅          ✅          ✅           ❌

  Update Task      ✅          ✅          ✅           ❌

  Move Task        ✅          ✅          ✅           ❌

  Delete Task      ✅          ✅          ✅           ❌

  Comment          ✅          ✅          ✅           ❌

  View Activity    ✅          ✅          ✅           ✅
  ------------------------------------------------------------------

# Vision_Scope_Audience

**Vision**

Build a production-grade real-time collaborative workspace platform that
enables teams to organize projects, track work, communicate, and
collaborate through live-updating boards, activities, notifications, and
shared workspaces.

The platform should demonstrate modern SaaS architecture and real-time
collaboration principles, including event-driven design, WebSockets,
concurrency control, asynchronous processing, and scalable domain
modeling.

The goal is not merely to manage tasks, but to provide a foundation for
team collaboration where multiple users can work together simultaneously
while maintaining consistency, performance, and reliability.

# Target Users

Primary Audience

- Small software teams

- Freelancers working with clients

- Startup teams

- Agencies managing multiple projects

# Project Scope

This project aims to build a production-grade collaborative workspace
platform centered around Kanban-based work management.

The primary objective is not to replicate every feature of existing
project management tools, but to explore and implement the architectural
challenges involved in modern collaborative software.

The project will focus on the following engineering domains:

1.  Multi-tenant team collaboration

2.  Real-time synchronization using WebSockets

3.  Event-driven backend architecture

4.  Concurrent user interaction and conflict resolution

5.  Scalable task and board management

6.  Role-based authorization

7.  Asynchronous processing through queues

8.  Search and data retrieval optimization

9.  Activity tracking and auditability

The project will intentionally avoid domains that would distract from
these goals, including:

- Instant messaging systems

- Video conferencing

- Enterprise workflow engines

- Full document collaboration suites

- CRM functionality

- Accounting or ERP features

- Native mobile applications

The project\'s success will be measured not by the number of features
implemented, but by the quality, scalability, and maintainability of the
architecture and collaboration experience.
