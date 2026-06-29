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
