<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $cv['header']['name'] ?? 'CV' }}</title>
    <style>
        /* ============================================================
           BASE RESET & FONTS
        ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ============================================================
           PAGE LAYOUT
        ============================================================ */
        .page {
            width: 100%;
            padding: 28px 36px;
        }

        /* ============================================================
           HEADER
        ============================================================ */
        .header {
            border-bottom: 2.5px solid #1a56db;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header h1 {
            font-size: 20pt;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }

        .header .title {
            font-size: 11pt;
            color: #1a56db;
            font-weight: 600;
            margin-top: 2px;
        }

        .contact-line {
            font-size: 8.5pt;
            color: #444;
            margin-top: 6px;
            line-height: 1.6;
        }

        .contact-line span {
            margin-right: 10px;
            display: inline-block;
        }

        /* ============================================================
           SECTIONS
        ============================================================ */
        .section {
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1a56db;
            border-bottom: 1px solid #c7d7f7;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }

        /* ============================================================
           SUMMARY
        ============================================================ */
        .summary-text {
            font-size: 9.5pt;
            color: #333;
            text-align: justify;
        }

        /* ============================================================
           SKILLS
        ============================================================ */
        .skills-list {
            display: block;
        }

        .skill-tag {
            display: inline-block;
            background: #eef2ff;
            color: #1a56db;
            border: 1px solid #c7d7f7;
            border-radius: 3px;
            padding: 2px 8px;
            font-size: 8.5pt;
            margin: 2px 3px 2px 0;
            font-weight: 500;
        }

        /* ============================================================
           EXPERIENCE / EDUCATION
        ============================================================ */
        .entry {
            margin-bottom: 10px;
        }

        .entry-header {
            display: table;
            width: 100%;
        }

        .entry-left {
            display: table-cell;
            width: 75%;
        }

        .entry-right {
            display: table-cell;
            width: 25%;
            text-align: right;
            vertical-align: top;
        }

        .entry-title {
            font-size: 10pt;
            font-weight: 700;
            color: #1a1a1a;
        }

        .entry-company {
            font-size: 9.5pt;
            color: #1a56db;
            font-weight: 600;
        }

        .entry-date {
            font-size: 8.5pt;
            color: #666;
        }

        .entry-location {
            font-size: 8.5pt;
            color: #666;
        }

        /* Highlights list */
        .highlights {
            margin: 4px 0 0 14px;
            padding: 0;
        }

        .highlights li {
            font-size: 9pt;
            color: #333;
            margin-bottom: 2px;
            list-style-type: disc;
        }

        .entry-description {
            font-size: 9pt;
            color: #333;
            margin-top: 3px;
        }

        .tech-tags {
            font-size: 8pt;
            color: #555;
            margin-top: 3px;
        }

        .tech-tags strong {
            color: #1a56db;
        }

        /* ============================================================
           PROJECTS
        ============================================================ */
        .project-title {
            font-size: 10pt;
            font-weight: 700;
            color: #1a1a1a;
        }

        .project-link {
            font-size: 8pt;
            color: #1a56db;
        }

        /* ============================================================
           CERTIFICATIONS
        ============================================================ */
        .cert-name {
            font-size: 9.5pt;
            font-weight: 600;
            color: #1a1a1a;
        }

        .cert-meta {
            font-size: 8.5pt;
            color: #555;
        }

        /* ============================================================
           TWO-COLUMN LAYOUT (for smaller sections)
        ============================================================ */
        .two-col {
            display: table;
            width: 100%;
        }

        .two-col .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        /* ============================================================
           DIVIDER
        ============================================================ */
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 8px 0;
        }

        /* ============================================================
           FOOTER
        ============================================================ */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            font-size: 7.5pt;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ================================================================
         HEADER
    ================================================================ --}}
    <div class="header">
        <h1>{{ $cv['header']['name'] ?? '' }}</h1>

        @if(!empty($cv['header']['title']))
            <div class="title">{{ $cv['header']['title'] }}</div>
        @endif

        @php
            $contact = $cv['header']['contact'] ?? [];
        @endphp

        <div class="contact-line">
            @if(!empty($contact['email']))
                <span>✉ {{ $contact['email'] }}</span>
            @endif
            @if(!empty($contact['phone']))
                <span>☏ {{ $contact['phone'] }}</span>
            @endif
            @if(!empty($contact['location']))
                <span>⚑ {{ $contact['location'] }}</span>
            @endif
            @if(!empty($contact['linkedin']))
                <span>in {{ $contact['linkedin'] }}</span>
            @endif
            @if(!empty($contact['github']))
                <span>⌥ {{ $contact['github'] }}</span>
            @endif
            @if(!empty($contact['portfolio']))
                <span>⊕ {{ $contact['portfolio'] }}</span>
            @endif
        </div>
    </div>

    {{-- ================================================================
         SUMMARY
    ================================================================ --}}
    @if(!empty($cv['summary']))
        <div class="section">
            <div class="section-title">Professional Summary</div>
            <p class="summary-text">{{ $cv['summary'] }}</p>
        </div>
    @endif

    {{-- ================================================================
         SKILLS
    ================================================================ --}}
    @if(!empty($cv['skills']['all']))
        <div class="section">
            <div class="section-title">Technical Skills</div>
            <div class="skills-list">
                @foreach($cv['skills']['all'] as $skill)
                    <span class="skill-tag">{{ $skill }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ================================================================
         EXPERIENCE
    ================================================================ --}}
    @if(!empty($cv['experience']))
        <div class="section">
            <div class="section-title">Professional Experience</div>

            @foreach($cv['experience'] as $exp)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-left">
                            <div class="entry-title">
                                {{ $exp['title'] ?? $exp['position'] ?? '' }}
                            </div>
                            <div class="entry-company">
                                {{ $exp['company'] ?? '' }}
                            </div>
                        </div>
                        <div class="entry-right">
                            @php
                                $startDate = $exp['start_date'] ?? '';
                                $endDate   = !empty($exp['end_date']) ? $exp['end_date'] : ((!empty($exp['current'])) ? 'Present' : '');
                                $dateRange = trim($startDate . ($endDate ? ' – ' . $endDate : ''));
                            @endphp
                            @if($dateRange)
                                <div class="entry-date">{{ $dateRange }}</div>
                            @endif
                        </div>
                    </div>

                    @if(!empty($exp['highlights']) && is_array($exp['highlights']))
                        <ul class="highlights">
                            @foreach($exp['highlights'] as $highlight)
                                @if(!empty(trim((string)$highlight)))
                                    <li>{{ $highlight }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @elseif(!empty($exp['description']))
                        <p class="entry-description">{{ $exp['description'] }}</p>
                    @endif

                    @if(!empty($exp['technologies']))
                        @php
                            $techs = is_array($exp['technologies']) ? array_values($exp['technologies']) : [];
                        @endphp
                        @if(!empty($techs))
                            <div class="tech-tags">
                                <strong>Technologies:</strong> {{ implode(', ', $techs) }}
                            </div>
                        @endif
                    @endif
                </div>
                @if(!$loop->last)<hr class="divider">@endif
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         EDUCATION
    ================================================================ --}}
    @if(!empty($cv['education']))
        <div class="section">
            <div class="section-title">Education</div>

            @foreach($cv['education'] as $edu)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-left">
                            <div class="entry-title">
                                {{ $edu['degree'] ?? '' }}
                                @if(!empty($edu['field_of_study']))
                                    — {{ $edu['field_of_study'] }}
                                @endif
                            </div>
                            <div class="entry-company">{{ $edu['institution'] ?? '' }}</div>
                            @if(isset($edu['grade']) && $edu['grade'] !== '' && $edu['grade'] !== null)
                                <div class="entry-location">Grade: {{ $edu['grade'] }}</div>
                            @endif
                        </div>
                        <div class="entry-right">
                            @php
                                $eduStart = $edu['start_date'] ?? '';
                                $eduEnd   = $edu['end_date'] ?? '';
                                $eduRange = trim($eduStart . ($eduEnd ? ' – ' . $eduEnd : ''));
                            @endphp
                            @if($eduRange)
                                <div class="entry-date">{{ $eduRange }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                @if(!$loop->last)<hr class="divider">@endif
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         PROJECTS
    ================================================================ --}}
    @if(!empty($cv['projects']))
        <div class="section">
            <div class="section-title">Projects</div>

            @foreach($cv['projects'] as $project)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-left">
                            <div class="project-title">{{ $project['title'] ?? '' }}</div>
                            @if(!empty($project['description']))
                                <p class="entry-description">{{ $project['description'] }}</p>
                            @endif
                            @if(!empty($project['technologies']) && is_array($project['technologies']))
                                <div class="tech-tags">
                                    <strong>Technologies:</strong> {{ implode(', ', $project['technologies']) }}
                                </div>
                            @endif
                            @if(!empty($project['link']))
                                <div class="project-link">{{ $project['link'] }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                @if(!$loop->last)<hr class="divider">@endif
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         CERTIFICATIONS
    ================================================================ --}}
    @if(!empty($cv['certifications']))
        <div class="section">
            <div class="section-title">Certifications</div>

            @foreach($cv['certifications'] as $cert)
                <div class="entry">
                    <div class="cert-name">{{ $cert['name'] ?? '' }}</div>
                    <div class="cert-meta">
                        @if(!empty($cert['issuer'])){{ $cert['issuer'] }}@endif
                        @if(!empty($cert['issued_at'])) | Issued: {{ $cert['issued_at'] }}@endif
                        @if(!empty($cert['expires_at'])) | Expires: {{ $cert['expires_at'] }}@endif
                        @if(!empty($cert['credential_id'])) | ID: {{ $cert['credential_id'] }}@endif
                    </div>
                </div>
                @if(!$loop->last)<hr class="divider">@endif
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         TRAININGS
    ================================================================ --}}
    @if(!empty($cv['trainings']))
        <div class="section">
            <div class="section-title">Training & Courses</div>

            @foreach($cv['trainings'] as $training)
                <div class="entry">
                    <div class="entry-header">
                        <div class="entry-left">
                            <div class="entry-title">{{ $training['title'] ?? '' }}</div>
                            @if(!empty($training['provider']))
                                <div class="entry-company">{{ $training['provider'] }}</div>
                            @endif
                            @if(!empty($training['description']))
                                <p class="entry-description">{{ $training['description'] }}</p>
                            @endif
                        </div>
                        <div class="entry-right">
                            @php
                                $tStart = $training['start_date'] ?? '';
                                $tEnd   = $training['end_date'] ?? '';
                                $tRange = trim($tStart . ($tEnd ? ' – ' . $tEnd : ''));
                            @endphp
                            @if($tRange)
                                <div class="entry-date">{{ $tRange }}</div>
                            @endif
                            @if(!empty($training['is_completed']))
                                <div class="entry-location" style="color:#16a34a;">✓ Completed</div>
                            @endif
                        </div>
                    </div>
                </div>
                @if(!$loop->last)<hr class="divider">@endif
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         LANGUAGES
    ================================================================ --}}
    @if(!empty($cv['languages']))
        <div class="section">
            <div class="section-title">Languages</div>
            <div class="skills-list">
                @foreach($cv['languages'] as $lang)
                    @if(is_array($lang))
                        <span class="skill-tag">
                            {{ $lang['language'] ?? $lang['name'] ?? '' }}
                            @if(!empty($lang['level'])) — {{ $lang['level'] }} @endif
                        </span>
                    @else
                        <span class="skill-tag">{{ $lang }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- ================================================================
         INTERESTS
    ================================================================ --}}
    @if(!empty($cv['interests']))
        <div class="section">
            <div class="section-title">Interests</div>
            <div class="skills-list">
                @foreach($cv['interests'] as $interest)
                    @if(is_array($interest))
                        <span class="skill-tag">{{ $interest['name'] ?? '' }}</span>
                    @else
                        <span class="skill-tag">{{ $interest }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- ================================================================
         FOOTER
    ================================================================ --}}
    <div class="footer">
        Generated by ATS CV System • {{ date('F Y') }}
    </div>

</div>
</body>
</html>
