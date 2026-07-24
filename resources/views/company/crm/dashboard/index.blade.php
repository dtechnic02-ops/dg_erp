@extends('company.layout')



@section('title', 'Relationship Dashboard')



@section('content')



<div class="dg-page">

    <header class="dg-toolbar">

        <div class="container-fluid">

            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">

                    <h1 class="h4 mb-0">Relationship Dashboard</h1>

                </div>

                <div class="flex-fill d-flex justify-content-end gap-2">

                    @if (userCan('view_crm_lead'))

                        <a href="{{ route('company.crm-leads.index') }}" class="btn btn-outline-secondary dg-btn">Customer Relationships</a>

                    @endif

                    @if (userCan('create_crm_lead'))

                        <a href="{{ route('company.crm-leads.create') }}" class="btn btn-success dg-btn">New Relationship</a>

                    @endif

                </div>

            </div>

        </div>

    </header>



    <main class="dg-container">

        <div class="container-fluid">

            <section class="dg-section">

                <div class="row g-3">

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">Today's Follow-up</div><div class="h4 mb-0">{{ $summary['todaysFollowUps'] }}</div></div></article></div>

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">Pending Tasks</div><div class="h4 mb-0">{{ $summary['pendingTasks'] }}</div></div></article></div>

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">Upcoming Meetings</div><div class="h4 mb-0">{{ $summary['upcomingMeetings'] }}</div></div></article></div>

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">Active Relationships</div><div class="h4 mb-0">{{ $summary['activeRelationships'] }}</div></div></article></div>

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">Won Opportunities</div><div class="h4 mb-0">{{ $summary['wonOpportunities'] }}</div></div></article></div>

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">Lost Opportunities</div><div class="h4 mb-0">{{ $summary['lostOpportunities'] }}</div></div></article></div>

                    <div class="col-md-3"><article class="card dg-card"><div class="card-body"><div class="text-muted small">New Relationships</div><div class="h4 mb-0">{{ $summary['monthlyRelationships'] }}</div></div></article></div>

                </div>

            </section>



            <section class="dg-section">

                <article class="card dg-card">

                    <header class="card-header dg-card-header"><h2 class="h6 mb-0">Employee Performance</h2></header>

                    <div class="card-body dg-card-body">

                        <div class="table-responsive">

                            <table class="table dg-table">

                                <thead>

                                    <tr>

                                        <th>#</th>

                                        <th>Employee</th>

                                        <th class="text-end">Relationships This Month</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse ($summary['employeePerformance'] as $row)

                                        <tr>

                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $row->assignedEmployee->full_name ?? 'Unassigned' }}</td>

                                            <td class="text-end">{{ $row->total_relationships }}</td>

                                        </tr>

                                    @empty

                                        <tr><td colspan="3" class="text-center">No relationship activity recorded this month.</td></tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                </article>

            </section>



            <div class="row g-3">

                <div class="col-lg-6">

                    <section class="dg-section">

                        <article class="card dg-card">

                            <header class="card-header dg-card-header"><h2 class="h6 mb-0">Today's Follow-up</h2></header>

                            <div class="card-body dg-card-body">

                                <div class="table-responsive">

                                    <table class="table dg-table">

                                        <thead><tr><th>No</th><th>Customer</th><th>Employee</th><th>Status</th></tr></thead>

                                        <tbody>

                                            @forelse ($summary['todayFollowUpList'] as $item)

                                                <tr>

                                                    <td>{{ $item->activity_no }}</td>

                                                    <td>{{ $item->lead->customer?->name ?? '-' }}</td>

                                                    <td>{{ $item->assignedEmployee->full_name ?? '-' }}</td>

                                                    <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>

                                                </tr>

                                            @empty

                                                <tr><td colspan="4" class="text-center">No follow-ups today.</td></tr>

                                            @endforelse

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </article>

                    </section>

                </div>

                <div class="col-lg-6">

                    <section class="dg-section">

                        <article class="card dg-card">

                            <header class="card-header dg-card-header"><h2 class="h6 mb-0">Pending Tasks</h2></header>

                            <div class="card-body dg-card-body">

                                <div class="table-responsive">

                                    <table class="table dg-table">

                                        <thead><tr><th>No</th><th>Type</th><th>Due</th><th>Employee</th></tr></thead>

                                        <tbody>

                                            @forelse ($summary['pendingTaskList'] as $task)

                                                <tr>

                                                    <td>{{ $task->activity_no }}</td>

                                                    <td>{{ ucfirst($task->task_type) }}</td>

                                                    <td>{{ $task->due_date?->format('d-m-Y') }}</td>

                                                    <td>{{ $task->assignedEmployee->full_name ?? '-' }}</td>

                                                </tr>

                                            @empty

                                                <tr><td colspan="4" class="text-center">No pending tasks.</td></tr>

                                            @endforelse

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </article>

                    </section>

                </div>

            </div>

        </div>

    </main>

</div>



@endsection


