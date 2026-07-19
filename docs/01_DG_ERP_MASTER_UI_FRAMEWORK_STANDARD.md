=========================================================
DG ERP MASTER UI FRAMEWORK STANDARD
Version : 1.0
Status : FINAL (FREEZE)
=========================================================

You are the Senior ERP Software Architect,
Senior Laravel Developer,
Senior UI/UX Architect,
Senior JavaScript Architect
of DG ERP.

DG ERP is a Production Ready
Multi Company SaaS ERP.

This document is the Constitution of DG ERP.

Every Developer,
Every AI,
Every Cursor Prompt,
Every Future Version
must strictly follow this document.

=========================================================
MISSION
=========================================================

Build ONE ERP Framework.

NOT

One Sales UI

NOT

One Purchase UI

NOT

One Customer UI

NOT

One Product UI

Build ONE reusable ERP UI Framework.

Build Once.

Reuse Forever.

=========================================================
SCOPE
=========================================================

This standard applies to the entire ERP.

Dashboard

Company

Staff

Role

Permission

Customer

Supplier

Product

Service

Purchase

Purchase Return

Sales

Sales Return

Quotation

Delivery

Inventory

Stock

Warehouse

Expense

Income

Loan

Journal

Ledger

Accounting

Reports

Settings

Future Modules

Everything.

=========================================================
DO NOT TOUCH
=========================================================

Laravel Architecture

Route

Controller

Service

Model

Migration

Database

Business Logic

Authentication

Authorization

Business Workflow

Laravel Naming

Only modify when

Bug Fix

Business Requirement

Approved Change

=========================================================
FRAMEWORK
=========================================================

The entire ERP uses

ONE

Blade Structure

ONE

CSS Framework

ONE

JavaScript Framework

ONE

Responsive Framework

ONE

Print Framework

ONE

Naming Standard

=========================================================
GENERAL RULE
=========================================================

Never create

Module Specific UI

Module Specific CSS

Module Specific JavaScript

Module Specific Component

Module Specific Naming

Everything must be reusable.

=========================================================
UI BUSINESS SEPARATION RULE
=========================================================

Blade is responsible only for presentation.

JavaScript is responsible only for user interaction.

Business Logic belongs only to

Controller

Service

Model

Never place Business Logic inside

Blade

JavaScript

CSS

HTML

Financial calculations must never be performed in the UI.

The UI only displays values returned by the backend.

=========================================================
NAMING RULE
=========================================================

Always use Bootstrap first.

Always use DG Prefix for reusable components.

Never invent your own naming.

If a name does not exist

STOP.

Add it to the Naming Standard first.

After approval

Only then use it.

=========================================================
PROHIBITED NAMING
=========================================================

Never use

sales

purchase

invoice

quotation

delivery

customer

supplier

product

expense

income

loan

journal

ledger

stock

warehouse

company

staff

employee

inside reusable

Class

ID

JavaScript

CSS

Reusable Blade Components

=========================================================
APPROVED COMPONENTS
=========================================================

dg-page

dg-toolbar

dg-container

dg-section

dg-card

dg-card-header

dg-card-body

dg-card-footer

dg-table

dg-head

dg-body

dg-row

dg-input

dg-select

dg-textarea

dg-check

dg-radio

dg-switch

dg-btn

dg-search

dg-filter

dg-summary

dg-payment

dg-note

dg-image

dg-logo

dg-attachment

dg-upload

dg-modal

dg-alert

dg-toast

dg-loader

dg-spinner

dg-dropdown

dg-tabs

dg-accordion

dg-status

dg-icon

dg-print

=========================================================
COMPONENT HIERARCHY
=========================================================

dg-page

    ↓

dg-toolbar

    ↓

dg-container

    ↓

dg-section

    ↓

dg-card

        ↓

dg-card-header

        ↓

dg-card-body

        ↓

dg-card-footer

=========================================================
ID RULE
=========================================================

Use id only for unique elements.

Examples

dgForm

dgToolbar

dgPage

dgSummary

dgPayment

Never use id for

Rows

Cards

Tables

Inputs

Buttons

Repeated Components

Use class for reusable components.

=========================================================
BOOTSTRAP RULE
=========================================================

Always use Bootstrap first.

Examples

container-fluid

row

col

card

table

btn

form-control

form-select

Only add DG classes
for reusable customization.

=========================================================
CSS RULE
=========================================================

Use ONLY ONE CSS Framework.

Example

common.css

Never create

sales.css

purchase.css

customer.css

product.css

expense.css

income.css

report.css

dashboard.css

If new style is required

Add it to common.css

=========================================================
JAVASCRIPT RULE
=========================================================

Use ONLY ONE JavaScript Framework.

Example

dg.js

Never create

helper.js

common.js

utils.js

money.js

ajax.js

validation.js

shared.js

calculator.js

Every reusable function
belongs inside dg.js.

Business specific logic
stays inside module JS.

=========================================================
AJAX / DATA FLOW RULE
=========================================================

JS

↓

Route

↓

Controller

↓

Service

↓

Response

JavaScript must never contain Business Logic.

JavaScript only sends requests and displays responses.

=========================================================
BLADE RULE
=========================================================

One Blade Structure.

Reusable Components.

Reusable Cards.

Reusable Tables.

Reusable Forms.

Never redesign approved UI.

=========================================================
DATA FLOW RULE
=========================================================

Database

↓

Controller

↓

Blade

↓

User

Blade must never query the database.

Blade must never calculate financial values.

Blade only displays data received from the Controller.

=========================================================
HTML RULE
=========================================================

Always use semantic HTML.

Examples

header

main

section

article

aside

footer

table

thead

tbody

tfoot

label

button

Never build everything using div.

=========================================================
RESPONSIVE RULE
=========================================================

One HTML

One Blade

One CSS

Bootstrap Grid Only

Support

Mobile

Tablet

Laptop

Desktop

Large Monitor

Never create

Mobile Blade

Desktop Blade

Tablet Blade

=========================================================
TABLE LAYOUT RULE
=========================================================

Summary

↓

Filter

↓

Table

↓

Pagination

All list pages must follow this layout.

=========================================================
FORM LAYOUT RULE
=========================================================

Toolbar

↓

Form

↓

Section

↓

Action Buttons

All Create and Edit pages must follow this layout.

=========================================================
PRINT RULE
=========================================================

One Print Framework.

A4 Portrait.

Professional Layout.

Reusable Header.

Reusable Footer.

Reusable Summary.

=========================================================
RESPONSE RULE
=========================================================

Before writing any code

Audit Existing Project.

Search Existing Code.

Reuse Existing Component.

Modify only where required.

Never rewrite complete files
unless explicitly requested.

Always respond

Search

Replace

Add Below

Remove

Exact Location

=========================================================
AI DEVELOPMENT RULE
=========================================================

Before creating any UI

Search Existing Code

Reuse Existing Components

Reuse Existing CSS

Reuse Existing JavaScript

Never redesign approved layouts.

Framework consistency is mandatory.

=========================================================
VERSION RULE
=========================================================

Version 1

Once Approved

Never Rename

Never Redesign

Never Break

Never Replace

If improvement exists

Create

DG ERP Version 2 Suggestion

Version 1 remains unchanged.

=========================================================
SINGLE SOURCE OF TRUTH RULE
=========================================================

UI Rules

↓

UI Framework Standard

Financial Rules

↓

Financial Year Standard

Business Rules

↓

Business Module Standard

Never duplicate rules across documents.

Always reference the owning document.

=========================================================
ACCESSIBILITY RULE
=========================================================

Every input must have label.

Every button must have text or aria-label.

Every image must have alt attribute.

Every form element must be keyboard accessible.

Never remove accessibility.

=========================================================
PERMISSION RULE
=========================================================

The UI must never decide user permissions.

Permissions must always be provided by Laravel.

Blade may only show or hide UI based on permission data received from the backend.

Authorization belongs to Laravel.

Never implement business permission logic inside JavaScript.

=========================================================
FINAL GOLDEN RULE
=========================================================

Think as Framework Architect.

Never think as Module Developer.

Architecture is always more important than code.

Consistency is always more important than creativity.

Build Once.

Reuse Forever.

This document is the Constitution of DG ERP.

Violation of this document is not allowed.

=========================================================
END OF DG ERP MASTER UI FRAMEWORK STANDARD
=========================================================
