<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ATS CV</title>

    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size:12px;
            color:#111;
            margin:40px;
            line-height:1.4;
        }

        .header{
            border-bottom:2px solid #000;
            padding-bottom:10px;
            margin-bottom:20px;
        }

        .name{
            font-size:28px;
            font-weight:bold;
        }

        .title{
            font-size:14px;
            color:#555;
            margin-top:2px;
        }

        .contact{
            font-size:11px;
            color:#666;
            margin-top:6px;
        }

        .section{
            margin-top:18px;
        }

        .section h2{
            font-size:13px;
            border-bottom:1px solid #ddd;
            padding-bottom:4px;
            margin-bottom:10px;
            text-transform:uppercase;
        }

        .item{ margin-bottom:12px; }

        .role{ font-weight:bold; }

        .company{
            color:#555;
            font-size:11px;
        }

        .small{
            font-size:11px;
            color:#666;
        }

        .badge{
            display:inline-block;
            padding:3px 7px;
            border:1px solid #ccc;
            margin:2px;
            font-size:11px;
        }
    </style>

</head>

<body>

{{-- SAFE DATA INIT --}}
@php
    $header = $cv['header'] ?? [];
    $contact = $header['contact'] ?? [];
@endphp

{{-- HEADER --}}
<div class="header">
    <div class="name">
        {{ $header['name'] ?? 'Unknown Candidate' }}
    </div>

    <div class="title">
        {{ $header['title'] ?? $cv['summary'] ?? 'Backend Developer' }}
    </div>

    <div class="contact">
        {{ $contact['email'] ?? '' }} |
        {{ $contact['phone'] ?? '' }} |
        {{ $contact['location'] ?? '' }}
        <br>
        {{ $contact['linkedin'] ?? '' }} |
        {{ $contact['github'] ?? '' }}
    </div>
</div>

{{-- SUMMARY --}}
<div class="section">
    <h2>Professional Summary</h2>
    <div>
        {{ $cv['summary'] ?? 'No summary provided' }}
    </div>
</div>

{{-- SKILLS --}}
<div class="section">
    <h2>Skills</h2>

    @foreach(($cv['skills']['backend'] ?? []) as $s)
        <span class="badge">{{ $s }}</span>
    @endforeach

    @foreach(($cv['skills']['tools'] ?? []) as $s)
        <span class="badge">{{ $s }}</span>
    @endforeach

    @foreach(($cv['skills']['languages'] ?? []) as $s)
        <span class="badge">{{ $s }}</span>
    @endforeach
</div>

{{-- EXPERIENCE --}}
<div class="section">
    <h2>Experience</h2>

    @foreach(($cv['experience'] ?? []) as $exp)
        <div class="item">

            <div class="role">
                {{ $exp['title'] ?? 'Developer' }} - {{ $exp['company'] ?? '' }}
            </div>

            @foreach(($exp['highlights'] ?? []) as $h)
                <div class="small">• {{ $h }}</div>
            @endforeach

        </div>
    @endforeach
</div>

{{-- EDUCATION --}}
<div class="section">
    <h2>Education</h2>

    @foreach(($cv['education'] ?? []) as $edu)
        <div class="item">
            <div class="role">{{ $edu['degree'] ?? '' }}</div>
            <div class="company">{{ $edu['institution'] ?? '' }}</div>
        </div>
    @endforeach
</div>

{{-- PROJECTS --}}
<div class="section">
    <h2>Projects</h2>

    @foreach(($cv['projects'] ?? []) as $p)
        <div class="item">
            <div class="role">{{ $p['title'] ?? '' }}</div>
            <div class="small">{{ $p['description'] ?? '' }}</div>
        </div>
    @endforeach
</div>

{{-- CERTIFICATIONS --}}
<div class="section">
    <h2>Certifications</h2>

    @foreach(($cv['certifications'] ?? []) as $cert)
        <div class="item">
            <div class="role">{{ $cert['name'] ?? '' }}</div>
            <div class="small">{{ $cert['issuer'] ?? '' }}</div>
        </div>
    @endforeach
</div>

</body>
</html>
