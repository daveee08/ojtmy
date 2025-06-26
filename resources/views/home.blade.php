@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'Home - CK AI Tools')

@section('styles')
    <style>
        .container {
            margin-top: 100px;
        }

        .hero {
            background-color: ##F5F5F5;
            border: 1px solid ##F5F5F5;
            padding: 50px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 2rem;
            color: #e91e63;
            font-weight: 700;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            max-width: 600px;
            margin: 15px auto 0;
        }

        .tool-card {
            background-color: #ffffff;
            border: 1px solid #eee;
            border-left: 5px solid #e91e63;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s ease;
        }

        .tool-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .tool-card h5 {
            font-size: 1.15rem;
            color: #e91e63;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .tool-card p {
            font-size: 0.95rem;
            color: #555;
            margin: 0;
        }

        .tool-card a {
            text-decoration: none;
            color: #e91e63;
        }

        .tool-card a:hover {
            text-decoration: underline;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="hero">
            <h1>Welcome to CK AI Tools</h1>
            <p>A supportive suite of tools to help young learners grow their reading and writing skills with clarity and
                confidence.</p>
        </div>

        <div class="tool-card" id="tool-leveler">
            <h5><a href="http://192.168.50.144:8000/leveler" target="_blank">Text Leveler</a></h5>
            <p>Adjust text difficulty to match your reading level and comprehension needs.</p>
        </div>

        <div class="tool-card" id="tool-summary">
            <h5><a href="http://192.168.50.238:8000/" target="_blank">Summarizer</a></h5>
            <p>Simplify long text into concise, easy-to-understand summaries.</p>
        </div>

        <div class="tool-card" id="tool-understanding">
            <h5><a href="http://127.0.0.1:8000/tutor" target="_blank">Understanding Tutor</a></h5>
            <p>Get writing feedback on grammar, structure, and clarity to build stronger writing skills.</p>
        </div>

        <div class="tool-card" id="tool-step by step">
            <h5><a href="http://127.0.0.1:8000/step-tutor" target="_blank">Step by Step Tutor</a></h5>
            <p>Get writing feedback on grammar, structure, and clarity to build stronger writing skills.</p>
        </div>

        <div class="tool-card" id="tool-rewriter">
            <h5><a href="http://192.168.50.123:8000/rewriter" target="_blank">Rewriter</a></h5>
            <p>Rephrase sentences to enhance expression and explore new ways of writing.</p>
        </div>

        <div class="tool-card" id="tool-proofreader">
            <h5><a href="http://192.168.50.18:5001/proofreader" target="_blank">Proofreader</a></h5>
            <p>Automatically catch and correct grammar, spelling, and punctuation errors.</p>
        </div>
    </div>
@endsection
