# Trust Score Calculation Examples

## Trust Score Formula

```
Base Score = 0

+ Government Registration Certificate (verified): +40
+ 80G Tax Exemption Certificate (verified): +20
+ Operating for 3+ years: +15
+ Address Proof (verified): +10
- Each Complaint: -30

Minimum Score for Approval: 60
Maximum Score: 100
```

## Example Scenarios

### Example 1: New NGO (Approved)
```
NGO: "Save Children Foundation"
Founded: 2021 (3 years old)
Documents:
  ✓ Registration Certificate (verified)
  ✓ 80G Certificate (verified)
  ✓ Address Proof (verified)
Complaints: 0

Calculation:
  Registration: +40
  80G: +20
  3+ years: +15
  Address: +10
  Complaints: 0
  
Total Score: 85/100 ✅ APPROVED
```

### Example 2: Established NGO (Approved)
```
NGO: "Education for All"
Founded: 2015 (9 years old)
Documents:
  ✓ Registration Certificate (verified)
  ✗ 80G Certificate (not available)
  ✓ Address Proof (verified)
Complaints: 0

Calculation:
  Registration: +40
  80G: 0
  3+ years: +15
  Address: +10
  Complaints: 0
  
Total Score: 65/100 ✅ APPROVED
```

### Example 3: Minimal Documentation (Approved)
```
NGO: "Rural Health Initiative"
Founded: 2018 (6 years old)
Documents:
  ✓ Registration Certificate (verified)
  ✗ 80G Certificate (not available)
  ✗ Address Proof (pending)
Complaints: 0

Calculation:
  Registration: +40
  80G: 0
  3+ years: +15
  Address: 0
  Complaints: 0
  
Total Score: 55/100 ❌ REJECTED (below 60)
```

### Example 4: New NGO with 80G (Approved)
```
NGO: "Clean Water Project"
Founded: 2023 (1 year old)
Documents:
  ✓ Registration Certificate (verified)
  ✓ 80G Certificate (verified)
  ✓ Address Proof (verified)
Complaints: 0

Calculation:
  Registration: +40
  80G: +20
  3+ years: 0 (only 1 year)
  Address: +10
  Complaints: 0
  
Total Score: 70/100 ✅ APPROVED
```

### Example 5: NGO with Complaints (Rejected)
```
NGO: "Questionable Charity"
Founded: 2019 (5 years old)
Documents:
  ✓ Registration Certificate (verified)
  ✓ 80G Certificate (verified)
  ✓ Address Proof (verified)
Complaints: 2

Calculation:
  Registration: +40
  80G: +20
  3+ years: +15
  Address: +10
  Complaints: -60 (2 × 30)
  
Total Score: 25/100 ❌ REJECTED
```

### Example 6: Auto-Suspended (10+ Complaints)
```
NGO: "Fraudulent NGO"
Founded: 2020 (4 years old)
Documents:
  ✓ Registration Certificate (verified)
  ✓ 80G Certificate (verified)
  ✓ Address Proof (verified)
Complaints: 10

Calculation:
  Registration: +40
  80G: +20
  3+ years: +15
  Address: +10
  Complaints: -300 (10 × 30)
  
Total Score: -215/100 (capped at 0)
Status: 🚫 AUTO-SUSPENDED
```

## SQL Query for Trust Score

```sql
-- Calculate trust score for an NGO
WITH ngo_docs AS (
    SELECT 
        ngo_id,
        SUM(CASE WHEN document_type = 'registration_certificate' AND verified = true THEN 40 ELSE 0 END) as reg_score,
        SUM(CASE WHEN document_type = '80g_certificate' AND verified = true THEN 20 ELSE 0 END) as cert_80g_score,
        SUM(CASE WHEN document_type = 'address_proof' AND verified = true THEN 10 ELSE 0 END) as address_score
    FROM ngo_documents
    WHERE ngo_id = 'NGO_ID_HERE'
    GROUP BY ngo_id
)
SELECT 
    n.id,
    n.ngo_name,
    COALESCE(d.reg_score, 0) +
    COALESCE(d.cert_80g_score, 0) +
    COALESCE(d.address_score, 0) +
    CASE WHEN (EXTRACT(YEAR FROM CURRENT_DATE) - n.founded_year) >= 3 THEN 15 ELSE 0 END -
    (n.complaint_count * 30) as trust_score
FROM ngos n
LEFT JOIN ngo_docs d ON n.id = d.ngo_id
WHERE n.id = 'NGO_ID_HERE';
```

## Trust Score Badges

### Display in UI
```javascript
function getTrustBadge(score) {
    if (score >= 80) return { text: 'Excellent', color: '#10B981', icon: '⭐⭐⭐' };
    if (score >= 70) return { text: 'Very Good', color: '#3B82F6', icon: '⭐⭐' };
    if (score >= 60) return { text: 'Good', color: '#F59E0B', icon: '⭐' };
    return { text: 'Below Standard', color: '#EF4444', icon: '⚠️' };
}
```

### HTML Display
```html
<div class="trust-score">
    <span class="score-value">85</span>
    <div class="score-bar">
        <div class="score-fill" style="width: 85%; background: #10B981;"></div>
    </div>
    <span class="score-badge excellent">⭐⭐⭐ Excellent</span>
</div>
```

## Fraud Detection Integration

### Automatic Flags
```php
// Check if trust score drops below threshold
if ($trust_score < 60 && $ngo['status'] === 'approved') {
    // Create fraud flag
    $db->query(
        "INSERT INTO fraud_flags (entity_type, entity_id, flag_type, severity, description) 
         VALUES (?, ?, ?, ?, ?)",
        ['ngo', $ngo_id, 'low_trust_score', 'medium', 'Trust score dropped below 60']
    );
    
    // Update NGO status
    $db->query("UPDATE ngos SET status = 'under_review' WHERE id = ?", [$ngo_id]);
}
```

## Admin Dashboard Display

```javascript
// Color-coded trust scores
function getScoreColor(score) {
    if (score >= 80) return '#10B981'; // Green
    if (score >= 70) return '#3B82F6'; // Blue
    if (score >= 60) return '#F59E0B'; // Orange
    return '#EF4444'; // Red
}

// Progress bar
<div class="trust-score-bar">
    <div class="fill" style="width: ${score}%; background: ${getScoreColor(score)}"></div>
</div>
```

## Recalculation Triggers

Trust score is recalculated when:
1. New document is verified
2. Complaint is filed
3. NGO details are updated
4. Admin manually triggers recalculation

```php
// Trigger recalculation
function recalculateTrustScore($ngo_id) {
    $score = calculateTrustScore($db, $ngo_id);
    $db->query("UPDATE ngos SET trust_score = ? WHERE id = ?", [$score, $ngo_id]);
    return $score;
}
```
