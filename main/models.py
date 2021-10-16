from django.db import models

# Create your models here.


class Course(models.Model):
    course_number = models.CharField(max_length=6, primary_key=True)
    name = models.CharField(max_length=33)
    points = models.DecimalField(max_digits=3, decimal_places=1)
    has_prerequisites = models.BooleanField()
    has_adjacents = models.BooleanField()
    original_preqs = models.CharField(max_length=100)
    original_adjs = models.CharField(max_length=100)
    semester = models.CharField(max_length=6)


class Prerequisite(models.Model):
    later_course = models.CharField(max_length=6)


class PrerequisiteSet(models.Model):
    prerequisite_id = models.ForeignKey(Prerequisite, on_delete=models.CASCADE)
    earlier_course = models.CharField(max_length=6)


class Adjacent(models.Model):
    requires = models.CharField(max_length=6)
    required = models.CharField(max_length=6)


class Contained(models.Model):
    course1 = models.CharField(max_length=6)
    course2 = models.CharField(max_length=6)
