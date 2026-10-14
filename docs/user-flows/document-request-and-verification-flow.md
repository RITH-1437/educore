# EduCore — Document Request & Digital Verification Flow

This document details how students request academic documents, how administrators process or waive document fees, how cryptographically signed PDF certificates are generated with embedded QR codes, and how third parties publicly verify their authenticity.

- **Status:** `[Implemented]`
- **Source Code Reference:** `DocumentsController.php`, `DocumentTypesController.php`, `DocumentVerificationService.php`, `backend/routes/web.php`
- **PlantUML Equivalent:** [`puml/document-request-and-verification-flow.puml`](puml/document-request-and-verification-flow.puml)

---

## 1. End-to-End Document Request & Issuance Workflow

```mermaid
flowchart TD
    subgraph StudentPortal["Student Request Workflow"]
        StartReq([Student opens /my-documents]) --> SelectDoc[Select Document Type:<br/>Certificate of Study, Official Transcript, Internship Recommendation]
        SelectDoc --> EnterPurpose[Enter Request Reason & Notes]
        EnterPurpose --> SubmitReq[Submit POST /my-documents]
        SubmitReq --> CheckFee{Document Type requires Fee?}
    end

    subgraph Billing["Fee Invoicing & Waiver"]
        CheckFee -- Yes (Fee > 0) --> CreateFeeInv[Generate Document Fee Invoice in invoices table<br/>status: 'pending_payment']
        CreateFeeInv --> CheckWaiver{Eligible for Waiver?<br/>Scholarship / Hardship}
        CheckWaiver -- Waived --> AdminWaive[Admin grants fee waiver in /documents]
        AdminWaive --> SetPendingReview[Update status: 'pending_review']
        CheckWaiver -- Standard --> PayOffline[Student pays cashier offline]
        PayOffline --> RecordCashier[Admin records payment in /invoices]
        RecordCashier --> SetPendingReview
        CheckFee -- No (Free) --> SetPendingReview
    end

    subgraph AdminAction["Administrative Processing"]
        SetPendingReview --> AdminQueue[Document Request appears in Department Admin queue]
        AdminQueue --> ReviewDecision{Approve Request?}
        ReviewDecision -- Reject --> MarkRejected[Set status: 'rejected' with reason]
        ReviewDecision -- Approve --> GeneratePDF[Invoke DocumentGenerationService]
        GeneratePDF --> CreateCryptoToken[Generate unique 64-char Verification Token<br/>Insert into document_verifications]
        CreateCryptoToken --> RenderTemplate[Render HTML/Blade Template with Student Data & Issue Date]
        RenderTemplate --> EmbedQR[Render SVG/PNG QR Code containing URL:<br/>https://educore.domain/verify/{token}]
        EmbedQR --> CompilePDF[Compile to PDF using DomPDF/Browsershot]
        CompilePDF --> UploadMinIO[Store signed PDF in MinIO S3 bucket]
        UploadMinIO --> SetIssued[Set status: 'issued' & file_path]
    end

    SetIssued --> NotifyStudent[Notify Student via In-App & Email]
    NotifyStudent --> DownloadDoc[Student downloads official PDF from portal]
```

---

## 2. Public QR Code Verification Flow

Any employer, embassy, or institution can verify an issued document without logging into the platform:

```mermaid
flowchart TD
    ScanQR([Third Party Scans QR Code on Paper / PDF Document]) --> URLHit[Browser navigates to GET /verify/{token}]
    URLHit --> RateLimit{Rate Limit Check:<br/>60 requests / min per IP}
    RateLimit -- Exceeded --> ThrottleErr[Display 429 Too Many Requests]

    RateLimit -- Pass --> LookupToken{Query document_verifications by token}
    LookupToken -- Not Found --> TokenNotFound[Display 'Invalid Document Verification Token']
    
    LookupToken -- Found --> CheckStatus{Document revoked or voided?}
    CheckStatus -- Revoked --> ShowRevoked[Display 'WARNING: This document has been officially revoked by the university']

    CheckStatus -- Valid & Active --> ShowValidBadge[Display Verified Green Seal:<br/>Document Title e.g. Official Transcript<br/>Student Full Name & Student ID<br/>Degree Program & Major<br/>Original Issue Date & Issuing Registrar]
```
