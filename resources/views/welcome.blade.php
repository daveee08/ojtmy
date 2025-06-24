@extends('layouts.navbar')

@section('title', 'Home - CK AI Tools')

@section('content')

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
        <h5><a href="http://192.168.50.127:8000/tutor" target="_blank">Understanding Tutor</a></h5>
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
@endsection
